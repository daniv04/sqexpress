<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points')->default(0)->after('locker_code');
        });

        // Populate with existing totals from invoices
        DB::statement('UPDATE users SET loyalty_points = (SELECT COALESCE(SUM(points_earned), 0) FROM invoices WHERE invoices.user_id = users.id)');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('loyalty_points');
        });
    }
};
