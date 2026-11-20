<?php

namespace Tests\Feature;

use App\Events\PackageStatusChanged;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackageStatusHistory;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class BulkMarkInvoicesDeliveredTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $method;

    private int $invoiceSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = ShippingMethod::factory()->create();
    }

    private function createInvoiceWithPackages(array $statuses): Invoice
    {
        $client = User::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'FAC-'.str_pad((string) ++$this->invoiceSeq, 6, '0', STR_PAD_LEFT),
            'user_id' => $client->id,
            'created_by' => $client->id,
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

        foreach ($statuses as $status) {
            Package::factory()->create([
                'user_id' => $client->id,
                'invoice_id' => $invoice->id,
                'shipping_method_id' => $this->method->id,
                'status' => $status,
            ]);
        }

        return $invoice;
    }

    public function test_bulk_action_marks_all_packages_of_selected_invoices_as_delivered(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->createInvoiceWithPackages(['ready_to_deliver', 'received_in_business']);
        $second = $this->createInvoiceWithPackages(['ready_to_deliver', 'delivered']);
        $notSelected = $this->createInvoiceWithPackages(['ready_to_deliver']);

        $this->actingAs($admin);

        Livewire::test(ListInvoices::class)
            ->callTableBulkAction('marcar_entregadas', [$first, $second])
            ->assertHasNoTableBulkActionErrors();

        $this->assertTrue($first->fresh()->isFullyDelivered());
        $this->assertTrue($second->fresh()->isFullyDelivered());
        $this->assertFalse($notSelected->fresh()->isFullyDelivered());

        // Already-delivered package is skipped: 2 + 1 transitions, not 4.
        Event::assertDispatchedTimes(PackageStatusChanged::class, 3);
        $this->assertSame(3, PackageStatusHistory::where('to_status', 'delivered')
            ->where('changed_by', $admin->id)
            ->count());
    }

    public function test_bulk_action_continues_when_one_invoice_cannot_be_delivered(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        // A prealerted package cannot transition to delivered.
        $broken = $this->createInvoiceWithPackages(['prealerted']);
        $ok = $this->createInvoiceWithPackages(['ready_to_deliver']);

        $this->actingAs($admin);

        Livewire::test(ListInvoices::class)
            ->callTableBulkAction('marcar_entregadas', [$broken, $ok])
            ->assertHasNoTableBulkActionErrors();

        $this->assertTrue($ok->fresh()->isFullyDelivered());
        $this->assertSame('prealerted', $broken->packages()->first()->status);
    }

    public function test_view_action_skips_canceled_packages_when_marking_delivered(): void
    {
        Event::fake([PackageStatusChanged::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoiceWithPackages(['canceled', 'ready_to_deliver']);

        $this->actingAs($admin);

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
            ->callAction('marcar_entregados')
            ->assertHasNoActionErrors();

        $statuses = $invoice->packages()->orderBy('id')->pluck('status')->all();
        $this->assertSame(['canceled', 'delivered'], $statuses);
        Event::assertDispatchedTimes(PackageStatusChanged::class, 1);
        $this->assertTrue($invoice->fresh()->isFullyDelivered());
    }

    public function test_invoice_with_delivered_and_canceled_packages_counts_as_fully_delivered(): void
    {
        $mixed = $this->createInvoiceWithPackages(['delivered', 'canceled']);
        $allCanceled = $this->createInvoiceWithPackages(['canceled']);
        $pending = $this->createInvoiceWithPackages(['delivered', 'ready_to_deliver']);
        $delivered = $this->createInvoiceWithPackages(['delivered']);

        $this->assertTrue($mixed->isFullyDelivered());
        $this->assertFalse($allCanceled->isFullyDelivered());
        $this->assertFalse($pending->isFullyDelivered());
        $this->assertTrue($delivered->isFullyDelivered());

        $this->assertEqualsCanonicalizing(
            [$mixed->id, $delivered->id],
            Invoice::fullyDelivered()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$allCanceled->id, $pending->id],
            Invoice::notFullyDelivered()->pluck('id')->all(),
        );
    }
}
