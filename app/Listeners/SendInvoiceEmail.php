<?php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Mail\InvoiceMail;
use App\Services\DbService\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function handle(InvoiceGenerated $event): void
    {
        $package = $event->package;
        $pdf = $this->invoiceService->buildPdf($package);

        Mail::to($package->user->email)->send(new InvoiceMail($package, $pdf->output()));
    }
}
