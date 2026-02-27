<?php

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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('tracking');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('approx_value', 10, 2)->nullable();
            $table->enum('status', ['prealerted', 'received_in_warehouse','assigned_flight','received_in_customs', 'received_in_business', 'ready_to_deliver','delivered','canceled'])->default('prealerted');
            $table->string('shelf_location')->nullable();
            $table->timestamp('prealerted_at')->nullable();
            $table->timestamps();
            $table->unique(['tracking', 'shipping_method_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
