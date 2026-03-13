<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            'Aéreo USA',
            'Marítimo USA',
            'Colombia',
        ];

        foreach ($methods as $name) {
            ShippingMethod::firstOrCreate(['name' => $name], ['active' => true]);
        }
    }
}
