<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('points_redeemed')->default(0)->after('points_earned');
            $table->decimal('points_discount_crc', 10, 2)->default(0)->after('points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_discount_crc']);
        });
    }
};
