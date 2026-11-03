<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once every package of an invoice is ready to deliver (or already
 * delivered), so the client gets a single "ready for pickup" email with the
 * invoice attached.
 */
class InvoiceReadyForDelivery
{
    use Dispatchable;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}
}
