<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('unit_type')->default('kg')->after('active');
            $table->decimal('price_per_unit', 10, 2)->default(0)->after('unit_type');
        });

        DB::table('settings')->where('key', 'price_per_kg')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'price_per_unit']);
        });
    }
};
