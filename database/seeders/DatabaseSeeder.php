<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario Admin
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'locker_code' => 'LKADMIN01',
        ]);

        // Crear usuario regular
        User::firstOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'role' => 'user',
            'locker_code' => 'LKUSER001',
        ]);

        $this->call([
            PackageDemoSeeder::class,
        ]);
    }
}
