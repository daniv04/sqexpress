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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('provincia_id')->nullable()->after('address')->constrained('provincias')->nullOnDelete();
            $table->foreignId('canton_id')->nullable()->after('provincia_id')->constrained('cantones')->nullOnDelete();
            $table->foreignId('distrito_id')->nullable()->after('canton_id')->constrained('distritos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['provincia_id']);
            $table->dropForeign(['canton_id']);
            $table->dropForeign(['distrito_id']);
            $table->dropColumn(['provincia_id', 'canton_id', 'distrito_id']);
        });
    }
};
