<?php

namespace App\Services\DbService;

use App\Models\Package;
use App\Models\PackageStatusHistory;

class PackageService
{
    public function createPackage(array $data): Package
    {
        $package = Package::create([
            'tracking' => $data['tracking'],
            'user_id' => $data['user_id'],
            'shipping_method_id' => $data['shipping_method_id'],
            'description' => $data['description'],
            'weight' => $data['weight'],
            'approx_value' => $data['approx_value'],
            'status' => 'prealerted',
            'prealerted_at' => now(),
        ]);
        return $package;
    }

    public function createFirstStatusHistory(int $id): void
    {
        PackageStatusHistory::create([
                'package_id' => $id,
                'from_status' => null,
                'to_status' => 'prealerted',
                'changed_by' => null, 
                'note' => 'Prealerta creada por usuario.',
            ]);
    }

    public function updatePackageStatus(Package $package, string $newStatus, ?int $changedBy = null, ?string $note = null): void
    {
        $oldStatus = $package->status;
        $package->update(['status' => $newStatus]);

        // Aquí podrías agregar lógica para registrar el cambio de estado en un historial
        // Por ejemplo, podrías crear un modelo PackageStatusHistory y guardar el cambio allí
    }
}