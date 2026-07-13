<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * packages.weight is now stored in kilograms. Rows written while the
     * grams-canonical code was live hold gram values; a courier package
     * never reaches 150 kg, so anything >= 150 is assumed to be grams.
     */
    public function up(): void
    {
        DB::table('packages')
            ->where('weight', '>=', 150)
            ->update(['weight' => DB::raw('ROUND(weight / 1000, 2)')]);
    }

    public function down(): void
    {
        // Irreversible: original unit per row is unknown after normalization.
    }
};
