<?php

namespace Tests\Feature;

use App\Events\PackageStatusChanged;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\PackageResource\Pages\ListPackages;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackageStatusHistory;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\DbService\PackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class MarkPackagesReadyToDeliverTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoice(User $user): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'FAC-000001',
            'user_id' => $user->id,
            'created_by' => $user->id,
            'subtotal' => 10,
            'discount_amount' => 0,
            'delivery_fee' => 0,
            'total' => 10,
            'exchange_rate' => 500,
            'total_crc' => 5000,
            'points_earned' => 50,
            'points_redeemed' => 0,
            'points_discount_crc' => 0,
            'generated_at' => now(),
        ]);
    }

    public function test_service_moves_only_received_in_business_packages_and_records_history(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $received = Package::factory()->create(['status' => 'received_in_business']);
        $alreadyReady = Package::factory()->create(['status' => 'ready_to_deliver']);
        $inTransit = Package::factory()->create(['status' => 'in_transit']);

        $moved = app(PackageService::class)->markPackagesAsReadyToDeliver(
            collect([$received, $alreadyReady, $inTransit]),
            changedBy: null,
        );

        $this->assertSame(1, $moved);
        $this->assertSame('ready_to_deliver', $received->fresh()->status);
        $this->assertSame('ready_to_deliver', $alreadyReady->fresh()->status);
        $this->assertSame('in_transit', $inTransit->fresh()->status);
        $this->assertSame(1, PackageStatusHistory::where('package_id', $received->id)
            ->where('from_status', 'received_in_business')
            ->where('to_status', 'ready_to_deliver')
            ->count());
        Event::assertDispatchedTimes(PackageStatusChanged::class, 1);
    }

    public function test_bulk_action_moves_selected_packages_to_ready_to_deliver(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $method = ShippingMethod::factory()->create();
        $first = Package::factory()->create(['status' => 'received_in_business', 'shipping_method_id' => $method->id]);
        $second = Package::factory()->create(['status' => 'received_in_business', 'shipping_method_id' => $method->id]);
        $notSelected = Package::factory()->create(['status' => 'received_in_business', 'shipping_method_id' => $method->id]);
        $wrongStatus = Package::factory()->create(['status' => 'prealerted', 'shipping_method_id' => $method->id]);

        $this->actingAs($admin);

        Livewire::test(ListPackages::class)
            ->callTableBulkAction('marcar_listos', [$first, $second, $wrongStatus])
            ->assertHasNoTableBulkActionErrors();

        $this->assertSame('ready_to_deliver', $first->fresh()->status);
        $this->assertSame('ready_to_deliver', $second->fresh()->status);
        $this->assertSame('received_in_business', $notSelected->fresh()->status);
        $this->assertSame('prealerted', $wrongStatus->fresh()->status);
        $this->assertSame($admin->id, PackageStatusHistory::where('package_id', $first->id)->value('changed_by'));
    }

    public function test_invoice_action_moves_invoice_packages_to_ready_to_deliver(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create();
        $invoice = $this->createInvoice($client);
        $inInvoice = Package::factory()->create([
            'user_id' => $client->id,
            'invoice_id' => $invoice->id,
            'status' => 'received_in_business',
        ]);
        $alreadyReady = Package::factory()->create([
            'user_id' => $client->id,
            'invoice_id' => $invoice->id,
            'status' => 'ready_to_deliver',
        ]);
        $outsideInvoice = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'received_in_business',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertActionVisible('marcar_listos')
            ->callAction('marcar_listos')
            ->assertHasNoActionErrors();

        $this->assertSame('ready_to_deliver', $inInvoice->fresh()->status);
        $this->assertSame('ready_to_deliver', $alreadyReady->fresh()->status);
        $this->assertSame('received_in_business', $outsideInvoice->fresh()->status);
        Event::assertDispatchedTimes(PackageStatusChanged::class, 1);
    }

    public function test_invoice_action_is_hidden_when_no_package_is_received_in_business(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create();
        $invoice = $this->createInvoice($client);
        Package::factory()->create([
            'user_id' => $client->id,
            'invoice_id' => $invoice->id,
            'status' => 'ready_to_deliver',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertActionHidden('marcar_listos');
    }
}
