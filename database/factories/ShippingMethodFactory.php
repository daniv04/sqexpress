<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Miami Aereo',
                'Marítimo',
                'Courier Express',
            ]),
            'active' => true,
            'unit_type' => 'kg',
            'price_per_unit' => fake()->randomFloat(2, 1, 10),
        ];
    }
}
