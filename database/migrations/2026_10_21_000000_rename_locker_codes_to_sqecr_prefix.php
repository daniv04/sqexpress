<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('locker_code', 'like', 'SQE%')
            ->where('locker_code', 'not like', 'SQECR%')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['locker_code' => 'SQECR' . substr($user->locker_code, 3)]);
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('locker_code', 'like', 'SQECR%')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['locker_code' => 'SQE' . substr($user->locker_code, 5)]);
            });
    }
};
