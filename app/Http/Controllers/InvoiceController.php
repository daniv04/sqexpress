<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\DbService\InvoiceService;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index()
    {
        $invoices = Invoice::where('user_id', Auth::id())
            ->with('packages.shippingMethod')
            ->orderBy('generated_at', 'desc')
            ->paginate(15);

        return view('mis-facturas', compact('invoices'));
    }

    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return $this->invoiceService->buildPdf($invoice)->download($invoice->invoice_number . '.pdf');
    }
}
