<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageStatusHistory;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class PackageDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'locker_code' => 'LKADMIN01',
                'active' => true,
            ]
        );

        $shippingMethod = ShippingMethod::firstOrCreate(
            ['name' => 'Miami Aereo'],
            ['active' => true]
        );

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

        foreach ($statuses as $index => $status) {
            $user = User::factory()->create([
                'email' => 'client' . ($index + 1) . '@example.com',
                'role' => 'user',
            ]);

            $package = Package::factory()->create([
                'tracking' => 'MIA-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'shipping_method_id' => $shippingMethod->id,
                'status' => $status,
                'prealerted_at' => now()->subDays(10 - $index),
            ]);

            $statusPath = [];
            foreach ($statuses as $possibleStatus) {
                $statusPath[] = $possibleStatus;
                if ($possibleStatus === $status) {
                    break;
                }
            }

            foreach ($statusPath as $step => $toStatus) {
                PackageStatusHistory::create([
                    'package_id' => $package->id,
                    'from_status' => $step === 0 ? null : $statusPath[$step - 1],
                    'to_status' => $toStatus,
                    'changed_by' => $step === 0 ? null : $admin->id,
                    'note' => $step === 0
                        ? 'Prealerta inicial registrada por sistema.'
                        : 'Cambio de estado aplicado por administrador.',
                    'created_at' => now()->subDays(max(0, 10 - $index - $step)),
                ]);
            }
        }
    }
}
