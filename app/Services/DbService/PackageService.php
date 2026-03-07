<?php

namespace App\Services\DbService;

use App\Enums\PackageStatus;
use App\Events\PackageStatusChanged;
use App\Models\Package;
use App\Models\PackageStatusHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use DomainException;

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
            'status' => PackageStatus::PREALERTED->value,
            'prealerted_at' => now(),
        ]);
        return $package;
    }

    public function createFirstStatusHistory(int $id): void
    {
        PackageStatusHistory::create([
                'package_id' => $id,
                'from_status' => null,
                'to_status' => PackageStatus::PREALERTED->value,
                'changed_by' => null, 
                'note' => 'Prealerta creada por usuario.',
            ]);
    }

    public function updatePackageStatus(
        Package $package,
        string $newStatus,
        ?int $changedBy = null,
        ?string $note = null,
        ?string $shelfLocation = null
    ): void
    {
        $fromStatus = PackageStatus::tryFrom($package->status);
        $toStatus = PackageStatus::tryFrom($newStatus);

        if (!$fromStatus || !$toStatus) {
            throw new InvalidArgumentException('Estado de paquete inválido.');
        }

        if (!$fromStatus->canTransitionTo($toStatus)) {
            throw new DomainException("Transición inválida: {$fromStatus->value} -> {$toStatus->value}.");
        }

        if ($toStatus === PackageStatus::RECEIVED_IN_BUSINESS && blank($shelfLocation)) {
            throw new DomainException('El estante (shelf_location) es obligatorio para el estado received_in_business.');
        }

        DB::transaction(function () use ($package, $fromStatus, $toStatus, $changedBy, $note, $shelfLocation): void {
            $updateData = ['status' => $toStatus->value];

            if ($toStatus === PackageStatus::RECEIVED_IN_BUSINESS) {
                $updateData['shelf_location'] = trim((string) $shelfLocation);
            }

            $package->update($updateData);

            PackageStatusHistory::create([
                'package_id' => $package->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);
        });

        PackageStatusChanged::dispatch($package, $fromStatus->value, $toStatus->value);
    }
}