<?php

use App\Models\Invoice;
use App\Models\Package;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Package::whereNotNull('invoice_number')->each(function ($package) {
            $invoice = Invoice::create([
                'invoice_number' => $package->invoice_number,
                'user_id' => $package->user_id,
                'created_by' => 1,
                'subtotal' => $package->service_cost ?? 0,
                'discount_amount' => $package->discount_amount ?? 0,
                'delivery_fee' => $package->delivery_fee ?? 0,
                'total' => ($package->service_cost ?? 0) - ($package->discount_amount ?? 0) + ($package->delivery_fee ?? 0),
                'points_earned' => $package->points_earned ?? 0,
                'generated_at' => $package->invoice_generated_at,
            ]);

            $package->update(['invoice_id' => $invoice->id]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Package::whereNotNull('invoice_id')->update(['invoice_id' => null]);
        Invoice::truncate();
    }
};
