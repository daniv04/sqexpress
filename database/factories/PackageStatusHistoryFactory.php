<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackageStatusHistory>
 */
class PackageStatusHistoryFactory extends Factory
{
    protected $model = PackageStatusHistory::class;

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
            'package_id' => Package::factory(),
            'from_status' => fake()->optional()->randomElement($statuses),
            'to_status' => fake()->randomElement($statuses),
            'changed_by' => fake()->boolean(80) ? User::factory() : null,
            'note' => fake()->optional()->sentence(8),
            'created_at' => now(),
        ];
    }
}
