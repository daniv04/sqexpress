<?php

namespace App\Services\DbService;

use App\Events\InvoiceGenerated;
use App\Models\Package;
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

    public function calculatePoints(float $serviceCost): int
    {
        return (int) round($serviceCost * 0.01);
    }

    public function generateAndPersistInvoice(Package $package, float $serviceCost, int $adminId): Package
    {
        DB::transaction(function () use ($package, $serviceCost): void {
            $invoiceNumber = $this->generateInvoiceNumber();
            $points = $this->calculatePoints($serviceCost);

            $package->update([
                'service_cost' => $serviceCost,
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
