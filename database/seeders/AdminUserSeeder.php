<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'josedavilo0408@gmail.com')->exists()) {
            return;
        }

        User::create([
            'name'     => 'Admin',
            'email'    => 'josedavilo0408@gmail.com',
            'password' => bcrypt('adminsqexpress'),
            'role'     => 'admin',
        ]);
    }
}
