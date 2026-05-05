<?php

namespace Database\Seeders;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener 2 usuarios que no sean admin (o crearlos)
        $user1 = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);
        $user2 = User::where('role', 'user')->where('id', '!=', $user1->id)->first() ?? User::factory()->create(['role' => 'user']);

        // Obtener un método de envío
        $shippingMethod = ShippingMethod::first() ?? ShippingMethod::factory()->create();

        // Crear 4 paquetes (2 para cada usuario) sin peso y en estado ready_to_deliver
        // Nota: weight está en libras (lbs)
        Package::factory()->count(2)->create([
            'user_id' => $user1->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => PackageStatus::READY_TO_DELIVER->value,
            'weight' => null,  // Sin peso asignado
            'service_cost' => null,  // Sin costo, se calculará al facturar
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $user2->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => PackageStatus::READY_TO_DELIVER->value,
            'weight' => null,  // Sin peso asignado
            'service_cost' => null,  // Sin costo, se calculará al facturar
        ]);

        $this->command->info('✅ Se crearon 4 paquetes:');
        $this->command->info("   - Usuario 1: {$user1->name} (ID: {$user1->id}) - 2 paquetes sin peso");
        $this->command->info("   - Usuario 2: {$user2->name} (ID: {$user2->id}) - 2 paquetes sin peso");
        $this->command->info('   - Todos en estado: Listo para Entregar');
    }
}
