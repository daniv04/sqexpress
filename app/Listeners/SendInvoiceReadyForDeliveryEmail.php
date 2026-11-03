<?php

namespace App\Listeners;

use App\Events\InvoiceReadyForDelivery;
use App\Mail\InvoiceReadyForDeliveryMail;
use App\Services\DbService\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReadyForDeliveryEmail implements ShouldQueue
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function handle(InvoiceReadyForDelivery $event): void
    {
        $invoice = $event->invoice;
        $pdf = $this->invoiceService->buildPdf($invoice);

        Mail::to($invoice->user->email)->send(new InvoiceReadyForDeliveryMail($invoice, $pdf->output()));
    }
}
