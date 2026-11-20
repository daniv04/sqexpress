<?php

namespace Tests\Feature;

use App\Events\InvoiceGenerated;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\DbService\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class EditInvoiceWeightsTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = ShippingMethod::factory()->create(['price_per_unit' => 10]);
    }

    /**
     * Invoice with one package of 3 kg at $10/kg: $30 → ₡15,000 → 150 points.
     */
    private function createInvoice(User $user, array $overrides = [], string $packageStatus = 'ready_to_deliver'): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'invoice_number' => 'FAC-000001',
            'user_id' => $user->id,
            'created_by' => $user->id,
            'subtotal' => 30,
            'discount_amount' => 0,
            'delivery_fee' => 0,
            'total' => 30,
            'exchange_rate' => 500,
            'total_crc' => 15000,
            'points_earned' => 150,
            'points_redeemed' => 0,
            'points_discount_crc' => 0,
            'generated_at' => now(),
        ], $overrides));

        Package::factory()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'shipping_method_id' => $this->method->id,
            'weight' => 3,
            'service_cost' => 30,
            'status' => $packageStatus,
        ]);

        return $invoice;
    }

    public function test_updates_weight_recalculates_totals_adjusts_points_and_resends(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create(['loyalty_points' => 150]);
        $invoice = $this->createInvoice($user);
        $package = $invoice->packages()->first();

        app(InvoiceService::class)->updatePackageWeights($invoice, [$package->id => 1]);

        $package->refresh();
        $invoice->refresh();
        $this->assertSame(1.0, (float) $package->weight);
        $this->assertSame('10.00', number_format((float) $package->service_cost, 2, '.', ''));
        $this->assertSame('FAC-000001', $invoice->invoice_number);
        $this->assertSame('10.00', (string) $invoice->subtotal);
        $this->assertSame('10.00', (string) $invoice->total);
        $this->assertSame('500.00', (string) $invoice->exchange_rate);
        $this->assertSame('5000.00', (string) $invoice->total_crc);
        $this->assertSame(50, $invoice->points_earned);

        // 150 points were credited at issue time; the -100 delta is removed.
        $this->assertSame(50, $user->fresh()->loyalty_points);

        Event::assertDispatched(InvoiceGenerated::class, fn (InvoiceGenerated $event) => $event->invoice->is($invoice));
    }

    public function test_keeps_new_client_discount_and_redeemed_points(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create(['loyalty_points' => 0]);
        $invoice = $this->createInvoice($user, [
            'discount_amount' => 3,
            'total' => 27,
            'points_redeemed' => 1000,
            'points_discount_crc' => 1000,
            'total_crc' => 12500,
            'points_earned' => 125,
        ]);
        $package = $invoice->packages()->first();

        app(InvoiceService::class)->updatePackageWeights($invoice, [$package->id => 2]);

        $invoice->refresh();
        $this->assertSame('20.00', (string) $invoice->subtotal);
        $this->assertSame('2.00', (string) $invoice->discount_amount);
        $this->assertSame('18.00', (string) $invoice->total);
        $this->assertSame('1000.00', (string) $invoice->points_discount_crc);
        $this->assertSame('8000.00', (string) $invoice->total_crc);
        $this->assertSame(80, $invoice->points_earned);
    }

    public function test_points_balance_never_goes_below_zero(): void
    {
        Event::fake([InvoiceGenerated::class]);

        // The client already spent the points this invoice credited.
        $user = User::factory()->create(['loyalty_points' => 20]);
        $invoice = $this->createInvoice($user);
        $package = $invoice->packages()->first();

        app(InvoiceService::class)->updatePackageWeights($invoice, [$package->id => 1]);

        $this->assertSame(0, $user->fresh()->loyalty_points);
    }

    public function test_ignores_packages_that_do_not_belong_to_the_invoice(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create(['loyalty_points' => 150]);
        $invoice = $this->createInvoice($user);
        $other = Package::factory()->create([
            'user_id' => $user->id,
            'shipping_method_id' => $this->method->id,
            'weight' => 5,
            'service_cost' => 50,
            'status' => 'ready_to_deliver',
        ]);

        app(InvoiceService::class)->updatePackageWeights($invoice, [$other->id => 1]);

        $this->assertSame(5.0, (float) $other->fresh()->weight);
        $this->assertSame('30.00', (string) $invoice->fresh()->subtotal);
    }

    public function test_rejects_paid_invoice(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create();
        $invoice = $this->createInvoice($user, ['paid_at' => now()]);
        $package = $invoice->packages()->first();

        $this->expectException(RuntimeException::class);

        try {
            app(InvoiceService::class)->updatePackageWeights($invoice, [$package->id => 1]);
        } finally {
            $this->assertSame(3.0, (float) $package->fresh()->weight);
            Event::assertNotDispatched(InvoiceGenerated::class);
        }
    }

    public function test_rejects_fully_delivered_invoice(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create();
        $invoice = $this->createInvoice($user, [], 'delivered');
        $package = $invoice->packages()->first();

        $this->expectException(RuntimeException::class);

        app(InvoiceService::class)->updatePackageWeights($invoice, [$package->id => 1]);
    }

    public function test_view_action_edits_weights_and_resends(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['loyalty_points' => 150]);
        $invoice = $this->createInvoice($user);
        $package = $invoice->packages()->first();

        $this->actingAs($admin);

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertActionVisible('editar_pesos')
            ->callAction('editar_pesos', [
                'package_weights' => [
                    ['id' => $package->id, 'weight' => 1],
                ],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(1.0, (float) $package->fresh()->weight);
        $this->assertSame('10.00', (string) $invoice->fresh()->total);
        Event::assertDispatched(InvoiceGenerated::class);
    }

    public function test_view_action_is_hidden_for_paid_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $invoice = $this->createInvoice($user, ['paid_at' => now()]);

        $this->actingAs($admin);

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertActionHidden('editar_pesos');
    }
}
