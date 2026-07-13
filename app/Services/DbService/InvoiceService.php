<?php

namespace App\Services\DbService;

use App\Events\InvoiceGenerated;
use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $max = Invoice::max('invoice_number');

        if (! $max) {
            return $this->formatInvoiceNumber(1);
        }

        $number = (int) substr($max, 4); // strip "FAC-"

        return $this->formatInvoiceNumber($number + 1);
    }

    public function isFirstInvoice(User $user): bool
    {
        return ! Invoice::where('user_id', $user->id)->exists();
    }

    public function calculateDiscount(float $serviceCost, bool $applyDiscount): float
    {
        return $applyDiscount ? round($serviceCost * 0.10, 2) : 0.0;
    }

    public function calculatePoints(float $totalAfterDiscount): int
    {
        // 1 loyalty point per USD dollar of the discounted total
        return (int) round($totalAfterDiscount);
    }

    /**
     * Generate an invoice for multiple packages from the same user.
     *
     * @param User $user
     * @param Collection $packages
     * @param float $deliveryFee
     * @param int $adminId
     * @param bool $applyNewClientDiscount Admin can opt out of the first-invoice discount.
     * @return Invoice
     */
    public function generateAndPersistInvoice(
        User $user,
        Collection $packages,
        float $deliveryFee,
        int $adminId,
        bool $applyNewClientDiscount = true
    ): Invoice {
        $invoice = DB::transaction(function () use ($user, $packages, $deliveryFee, $adminId, $applyNewClientDiscount): Invoice {
            $subtotal = $packages->sum('service_cost');
            $isFirst = $applyNewClientDiscount && $this->isFirstInvoice($user);
            $discount = $this->calculateDiscount($subtotal, $isFirst);
            $total = $subtotal - $discount + $deliveryFee;
            $points = $this->calculatePoints($total);

            $rate = (float) AppSetting::get('exchange_rate_usd_crc', 0);
            $totalCrc = $rate > 0 ? round($total * $rate, 2) : null;

            $invoiceNumber = $this->generateInvoiceNumber();

            // Create invoice record
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'created_by' => $adminId,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'exchange_rate' => $rate > 0 ? $rate : null,
                'total_crc' => $totalCrc,
                'points_earned' => $points,
                'generated_at' => now(),
            ]);

            // Assign packages to invoice
            foreach ($packages as $package) {
                $package->update(['invoice_id' => $invoice->id]);
            }

            $user->increment('loyalty_points', $points);

            return $invoice;
        });

        InvoiceGenerated::dispatch($invoice);

        return $invoice;
    }

    public function resendInvoice(Invoice $invoice): void
    {
        InvoiceGenerated::dispatch($invoice);
    }

    public function buildPdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['user.provincia', 'user.canton', 'user.distrito', 'packages.shippingMethod']);

        return Pdf::loadView('pdfs.invoice', compact('invoice'))->setPaper('letter', 'portrait');
    }

    private function formatInvoiceNumber(int $number): string
    {
        return 'FAC-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
