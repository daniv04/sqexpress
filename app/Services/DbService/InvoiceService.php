<?php

namespace App\Services\DbService;

use App\Events\InvoiceGenerated;
use App\Models\Package;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $max = Package::whereNotNull('invoice_number')->max('invoice_number');

        if (! $max) {
            return $this->formatInvoiceNumber(1);
        }

        $number = (int) substr($max, 4); // strip "FAC-"

        return $this->formatInvoiceNumber($number + 1);
    }

    public function isFirstInvoice(User $user, Package $currentPackage): bool
    {
        return ! Package::where('user_id', $user->id)
            ->where('id', '!=', $currentPackage->id)
            ->whereNotNull('invoice_number')
            ->exists();
    }

    public function calculateDiscount(float $serviceCost, bool $applyDiscount): float
    {
        return $applyDiscount ? round($serviceCost * 0.10, 2) : 0.0;
    }

    public function calculatePoints(float $totalAfterDiscount): int
    {
        return (int) round($totalAfterDiscount * 0.01);
    }

    public function generateAndPersistInvoice(Package $package, float $serviceCost, int $adminId, float $deliveryFee = 0.0): Package
    {
        DB::transaction(function () use ($package, $serviceCost, $deliveryFee): void {
            $invoiceNumber = $this->generateInvoiceNumber();
            $isFirst = $this->isFirstInvoice($package->user, $package);
            $discount = $this->calculateDiscount($serviceCost, $isFirst);
            $total = $serviceCost - $discount + $deliveryFee;
            $points = $this->calculatePoints($total);

            $package->update([
                'service_cost' => $serviceCost,
                'discount_amount' => $discount,
                'delivery_fee' => $deliveryFee,
                'invoice_number' => $invoiceNumber,
                'invoice_generated_at' => now(),
                'points_earned' => $points,
            ]);
        });

        $package->refresh();

        InvoiceGenerated::dispatch($package);

        return $package;
    }

    public function buildPdf(Package $package): \Barryvdh\DomPDF\PDF
    {
        $package->load(['user.provincia', 'user.canton', 'user.distrito', 'shippingMethod']);

        return Pdf::loadView('pdfs.invoice', compact('package'))->setPaper('letter', 'portrait');
    }

    private function formatInvoiceNumber(int $number): string
    {
        return 'FAC-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
