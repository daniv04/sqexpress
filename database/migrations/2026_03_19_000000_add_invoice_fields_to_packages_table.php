<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('service_cost', 10, 2)->nullable()->after('approx_value');
            $table->string('invoice_number', 20)->nullable()->unique()->after('service_cost');
            $table->timestamp('invoice_generated_at')->nullable()->after('invoice_number');
            $table->unsignedInteger('points_earned')->nullable()->default(0)->after('invoice_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['service_cost', 'invoice_number', 'invoice_generated_at', 'points_earned']);
        });
    }
};
