<?php

namespace Tests\Feature;

use App\Events\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Services\DbService\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class AddPackagesToInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoiceWithPackage(User $user, array $invoiceOverrides = [], string $packageStatus = 'received_in_business'): Invoice
    {
        $invoice = Invoice::create(array_merge([
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
        ], $invoiceOverrides));

        Package::factory()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'service_cost' => 10,
            'status' => $packageStatus,
        ]);

        return $invoice;
    }

    private function createBillablePackage(User $user, float $serviceCost = 20): Package
    {
        return Package::factory()->create([
            'user_id' => $user->id,
            'service_cost' => $serviceCost,
            'status' => 'ready_to_deliver',
        ]);
    }

    public function test_adds_packages_recalculates_totals_with_stored_rate_and_credits_only_points_delta(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create(['loyalty_points' => 50]);
        $invoice = $this->createInvoiceWithPackage($user);
        $package = $this->createBillablePackage($user);

        app(InvoiceService::class)->addPackagesToInvoice($invoice, collect([$package->id]));

        $invoice->refresh();
        $this->assertSame($invoice->id, $package->fresh()->invoice_id);
        $this->assertSame('FAC-000001', $invoice->invoice_number);
        $this->assertSame('30.00', (string) $invoice->subtotal);
        $this->assertSame('30.00', (string) $invoice->total);
        $this->assertSame('500.00', (string) $invoice->exchange_rate);
        $this->assertSame('15000.00', (string) $invoice->total_crc);
        $this->assertSame(150, $invoice->points_earned);

        // 50 points were credited at issue time; only the +100 delta is added.
        $this->assertSame(150, $user->fresh()->loyalty_points);

        Event::assertDispatched(InvoiceGenerated::class, fn (InvoiceGenerated $event) => $event->invoice->is($invoice));
    }

    public function test_keeps_new_client_discount_and_redeemed_points_from_the_original_invoice(): void
    {
        Event::fake([InvoiceGenerated::class]);

        $user = User::factory()->create(['loyalty_points' => 35]);
        $invoice = $this->createInvoiceWithPackage($user, [
            'discount_amount' => 1,
            'total' => 9,
            'total_crc' => 3500,
            'points_earned' => 35,
            'points_redeemed' => 1000,
            'points_discount_crc' => 1000,
        ]);
        $package = $this->createBillablePackage($user);

        app(InvoiceService::class)->addPackagesToInvoice($invoice, collect([$package->id]));

        $invoice->refresh();
        $this->assertSame('3.00', (string) $invoice->discount_amount);
        $this->assertSame('27.00', (string) $invoice->total);
        $this->assertSame('12500.00', (string) $invoice->total_crc); // 27 * 500 - 1000
        $this->assertSame(1000, $invoice->points_redeemed);
        $this->assertSame(125, $invoice->points_earned);
        $this->assertSame(125, $user->fresh()->loyalty_points);
    }

    public function test_rejects_paid_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithPackage($user, ['paid_at' => now()]);
        $package = $this->createBillablePackage($user);

        $this->assertRejectedWithoutChanges($invoice, $package);
    }

    public function test_rejects_fully_delivered_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithPackage($user, [], 'delivered');
        $package = $this->createBillablePackage($user);

        $this->assertRejectedWithoutChanges($invoice, $package);
    }

    public function test_rejects_package_from_another_client(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithPackage($user);
        $foreignPackage = $this->createBillablePackage(User::factory()->create());

        $this->assertRejectedWithoutChanges($invoice, $foreignPackage);
    }

    public function test_rejects_package_already_in_another_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithPackage($user);
        $otherInvoice = $this->createInvoiceWithPackage($user, ['invoice_number' => 'FAC-000002']);
        $package = $this->createBillablePackage($user);
        $package->update(['invoice_id' => $otherInvoice->id]);

        $this->assertRejectedWithoutChanges($invoice, $package, $otherInvoice->id);
    }

    public function test_rejects_package_in_non_billable_status(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoiceWithPackage($user);
        $package = $this->createBillablePackage($user);
        $package->update(['status' => 'prealerted']);

        $this->assertRejectedWithoutChanges($invoice, $package);
    }

    private function assertRejectedWithoutChanges(Invoice $invoice, Package $package, ?int $expectedInvoiceId = null): void
    {
        Event::fake([InvoiceGenerated::class]);
        $pointsBefore = $invoice->user->fresh()->loyalty_points;

        try {
            app(InvoiceService::class)->addPackagesToInvoice($invoice, collect([$package->id]));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame($expectedInvoiceId, $package->fresh()->invoice_id);
        $this->assertSame('10.00', (string) $invoice->fresh()->subtotal);
        $this->assertSame($pointsBefore, $invoice->user->fresh()->loyalty_points);
        Event::assertNotDispatched(InvoiceGenerated::class);
    }
}
