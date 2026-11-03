<?php

namespace Tests\Feature;

use App\Events\InvoiceReadyForDelivery;
use App\Events\PackageStatusChanged;
use App\Listeners\SendInvoiceReadyForDeliveryEmail;
use App\Mail\InvoiceReadyForDeliveryMail;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\DbService\PackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceReadyForDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = ShippingMethod::factory()->create();
    }

    private function createInvoice(User $user): Invoice
    {
        return Invoice::create([
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
        ]);
    }

    private function createPackage(User $user, ?Invoice $invoice, string $status): Package
    {
        return Package::factory()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice?->id,
            'shipping_method_id' => $this->method->id,
            'status' => $status,
            'service_cost' => 15,
        ]);
    }

    public function test_dispatches_once_when_last_invoice_package_becomes_ready(): void
    {
        Event::fake([InvoiceReadyForDelivery::class, PackageStatusChanged::class]);

        $user = User::factory()->create();
        $invoice = $this->createInvoice($user);
        $this->createPackage($user, $invoice, 'ready_to_deliver');
        $last = $this->createPackage($user, $invoice, 'received_in_business');

        app(PackageService::class)->updatePackageStatus($last, 'ready_to_deliver');

        Event::assertDispatchedTimes(InvoiceReadyForDelivery::class, 1);
        Event::assertDispatched(InvoiceReadyForDelivery::class, fn ($e) => $e->invoice->is($invoice));
        // The regular per-package status email still fires.
        Event::assertDispatched(PackageStatusChanged::class);
    }

    public function test_does_not_dispatch_while_invoice_has_packages_not_ready(): void
    {
        Event::fake([InvoiceReadyForDelivery::class, PackageStatusChanged::class]);

        $user = User::factory()->create();
        $invoice = $this->createInvoice($user);
        $first = $this->createPackage($user, $invoice, 'received_in_business');
        $this->createPackage($user, $invoice, 'received_in_business');

        app(PackageService::class)->updatePackageStatus($first, 'ready_to_deliver');

        Event::assertNotDispatched(InvoiceReadyForDelivery::class);
    }

    public function test_bulk_ready_dispatches_a_single_event_per_invoice(): void
    {
        Event::fake([InvoiceReadyForDelivery::class, PackageStatusChanged::class]);

        $user = User::factory()->create();
        $invoice = $this->createInvoice($user);
        $a = $this->createPackage($user, $invoice, 'received_in_business');
        $b = $this->createPackage($user, $invoice, 'received_in_business');

        app(PackageService::class)->markPackagesAsReadyToDeliver(collect([$a, $b]));

        Event::assertDispatchedTimes(InvoiceReadyForDelivery::class, 1);
        Event::assertDispatchedTimes(PackageStatusChanged::class, 2);
    }

    public function test_does_not_dispatch_for_package_without_invoice(): void
    {
        Event::fake([InvoiceReadyForDelivery::class, PackageStatusChanged::class]);

        $user = User::factory()->create();
        $package = $this->createPackage($user, null, 'received_in_business');

        app(PackageService::class)->updatePackageStatus($package, 'ready_to_deliver');

        Event::assertNotDispatched(InvoiceReadyForDelivery::class);
    }

    public function test_listener_sends_mail_with_invoice_pdf_and_amount(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $invoice = $this->createInvoice($user);
        $this->createPackage($user, $invoice, 'ready_to_deliver');

        app(SendInvoiceReadyForDeliveryEmail::class)->handle(new InvoiceReadyForDelivery($invoice));

        Mail::assertSent(InvoiceReadyForDeliveryMail::class, function (InvoiceReadyForDeliveryMail $mail) use ($invoice) {
            $mail->assertSeeInHtml('₡15,000.00');
            $mail->assertSeeInHtml($invoice->invoice_number);
            $mail->assertHasSubject('Tus paquetes están listos para entregar — FAC-000001');

            return $mail->hasTo('cliente@example.com')
                && count($mail->attachments()) === 1;
        });
    }
}
