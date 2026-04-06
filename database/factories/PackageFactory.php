<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $statuses = [
            'prealerted',
            'received_in_warehouse',
            'assigned_flight',
            'received_in_customs',
            'received_in_business',
            'ready_to_deliver',
            'delivered',
            'canceled',
        ];

        return [
            'tracking' => strtoupper(fake()->bothify('TRK########')),
            'user_id' => User::factory(),
            'shipping_method_id' => ShippingMethod::factory(),
            'description' => fake()->sentence(6),
            'weight' => fake()->randomFloat(2, 0.5, 30),
            'approx_value' => fake()->randomFloat(2, 20, 1500),
            'status' => fake()->randomElement($statuses),
            'shelf_location' => fake()->optional()->bothify('A-##'),
            'prealerted_at' => fake()->optional()->dateTimeBetween('-20 days', 'now'),
        ];
    }
}
