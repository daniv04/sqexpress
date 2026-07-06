<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite (usado en tests) no fuerza el enum a nivel de columna, solo MySQL lo necesita.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE packages MODIFY status ENUM(
            'prealerted','received_in_warehouse','assigned_flight','in_transit',
            'received_in_customs','customs_process_finished','received_in_business',
            'ready_to_deliver','delivered','canceled'
        ) NOT NULL DEFAULT 'prealerted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE packages MODIFY status ENUM(
            'prealerted','received_in_warehouse','assigned_flight','in_transit',
            'received_in_customs','received_in_business','ready_to_deliver','delivered','canceled'
        ) NOT NULL DEFAULT 'prealerted'");
    }
};
