<?php

namespace App\Services\DbService;

use App\Enums\PackageStatus;
use App\Events\PackageDeletedByAdmin;
use App\Events\PackageStatusChanged;
use App\Events\PackageUpdatedByAdmin;
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

    public function adminUpdatePackage(Package $package, array $data, ?int $changedBy = null): Package
    {
        $whitelist = ['tracking', 'description', 'weight', 'approx_value', 'shelf_location', 'shipping_method_id'];
        $decimalFields = ['weight', 'approx_value'];

        $filtered = array_intersect_key($data, array_flip($whitelist));

        $changes = [];
        foreach ($filtered as $field => $newValue) {
            $oldValue = $package->getAttribute($field);

            $isDifferent = in_array($field, $decimalFields, true)
                ? round((float) $oldValue, 2) !== round((float) $newValue, 2)
                : (string) $oldValue !== (string) $newValue;

            if ($isDifferent) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        DB::transaction(function () use ($package, $filtered): void {
            $package->update($filtered);
        });

        if (!empty($changes)) {
            PackageUpdatedByAdmin::dispatch($package, $changes);
        }

        return $package;
    }

    public function adminDeletePackage(Package $package, ?int $deletedBy = null): void
    {
        if ($package->hasInvoice()) {
            throw new DomainException('No se puede eliminar un paquete que ya pertenece a una factura.');
        }

        $tracking = $package->tracking;
        $userEmail = $package->user->email;
        $userName = $package->user->name;
        $description = $package->description;

        DB::transaction(function () use ($package): void {
            $package->delete();
        });

        PackageDeletedByAdmin::dispatch($tracking, $userEmail, $userName, $description);
    }
}