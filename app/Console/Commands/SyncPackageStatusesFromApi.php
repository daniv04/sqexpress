<?php

namespace App\Console\Commands;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use App\Services\DbService\PackageService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPackageStatusesFromApi extends Command
{
    protected $signature = 'packages:sync-statuses';

    protected $description = 'Sincroniza estados de paquetes consultando la API externa de logística';

    private const API_URL = 'https://logis-tico.com/api';

    private const API_STATE_MAP = [
        'Recibido en Miami'      => PackageStatus::RECEIVED_IN_WAREHOUSE,
        'Vuelo Asignado'         => PackageStatus::ASSIGNED_FLIGHT,
        'Aduana'                 => PackageStatus::RECEIVED_IN_CUSTOMS,
        'Recibido en Costa Rica' => PackageStatus::RECEIVED_IN_BUSINESS,
    ];

    private const SYNCABLE_STATUSES = [
        PackageStatus::PREALERTED->value,
        PackageStatus::RECEIVED_IN_WAREHOUSE->value,
        PackageStatus::ASSIGNED_FLIGHT->value,
        PackageStatus::RECEIVED_IN_CUSTOMS->value,
    ];

    public function handle(PackageService $packageService): int
    {
        $this->info('Consultando API externa...');

        try {
            $response = Http::timeout(30)->post(self::API_URL, [
                'action' => 'readTrackingStatusSQ',
            ]);

            if (! $response->successful()) {
                $this->fail("API respondió con código {$response->status()}");
                $this->notifyAdmins('Error al sincronizar estados', "La API respondió con código HTTP {$response->status()}.");

                return self::FAILURE;
            }

            $apiData = $response->json();
        } catch (\Throwable $e) {
            Log::error('SyncPackageStatusesFromApi: error al llamar API', ['error' => $e->getMessage()]);
            $this->notifyAdmins('Error al sincronizar estados', "No se pudo conectar con la API: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Build lookup: tracking => [estado, pesoFinal]
        $apiLookup = [];
        foreach ($apiData as $item) {
            if (isset($item['seguimiento'])) {
                $apiLookup[$item['seguimiento']] = $item;
            }
        }

        $this->info('Paquetes en API: ' . count($apiLookup));

        $packages = Package::whereIn('status', self::SYNCABLE_STATUSES)->get();

        $this->info("Paquetes a revisar: {$packages->count()}");

        $updated = 0;
        $skipped = 0;

        foreach ($packages as $package) {
            if (! isset($apiLookup[$package->tracking])) {
                $skipped++;
                continue; // Not in API yet (not received in Miami)
            }

            $apiItem = $apiLookup[$package->tracking];
            $apiEstado = $apiItem['estado'] ?? null;

            if (! isset(self::API_STATE_MAP[$apiEstado])) {
                Log::warning('SyncPackageStatusesFromApi: estado desconocido en API', [
                    'tracking' => $package->tracking,
                    'estado'   => $apiEstado,
                ]);
                $skipped++;
                continue;
            }

            $targetStatus = self::API_STATE_MAP[$apiEstado];

            if ($this->ordinal($targetStatus->value) <= $this->ordinal($package->status)) {
                $skipped++; // Manual change was ahead or same state — respect it
                continue;
            }

            // Advance step by step through the state machine
            try {
                $this->advanceToTarget($package, $targetStatus, $apiItem['pesoFinal'] ?? null, $packageService);
                $updated++;
            } catch (\Throwable $e) {
                Log::warning('SyncPackageStatusesFromApi: error al actualizar paquete', [
                    'tracking' => $package->tracking,
                    'error'    => $e->getMessage(),
                ]);
                $this->notifyAdmins(
                    'Error al sincronizar estado de paquete',
                    "Paquete {$package->tracking}: {$e->getMessage()}"
                );
            }
        }

        $this->info("Actualizados: {$updated} | Omitidos: {$skipped}");

        return self::SUCCESS;
    }

    private function advanceToTarget(
        Package $package,
        PackageStatus $target,
        ?string $pesoFinal,
        PackageService $packageService
    ): void {
        // Walk step by step through allowed transitions until we reach the target
        while ($this->ordinal($package->status) < $this->ordinal($target->value)) {
            $current = PackageStatus::from($package->status);
            $nextAllowed = $current->nextAllowedStatuses();

            // Pick the next step toward the target (first non-CANCELED option)
            $nextStep = null;
            foreach ($nextAllowed as $allowed) {
                if ($allowed !== PackageStatus::CANCELED) {
                    $nextStep = $allowed;
                    break;
                }
            }

            if ($nextStep === null) {
                break; // No valid transition available
            }

            // Update weight before transitioning to RECEIVED_IN_WAREHOUSE
            if ($nextStep === PackageStatus::RECEIVED_IN_WAREHOUSE && $pesoFinal !== null) {
                $package->update(['weight' => (float) $pesoFinal]);
            }

            $shelfLocation = $nextStep === PackageStatus::RECEIVED_IN_BUSINESS ? 'Pendiente' : null;

            $packageService->updatePackageStatus(
                package: $package,
                newStatus: $nextStep->value,
                changedBy: null,
                note: 'Sincronizado automáticamente vía API',
                shelfLocation: $shelfLocation,
            );

            $package->refresh();
        }
    }

    private function ordinal(string $status): int
    {
        return match ($status) {
            'prealerted'            => 0,
            'received_in_warehouse' => 1,
            'assigned_flight'       => 2,
            'received_in_customs'   => 3,
            'received_in_business'  => 4,
            'ready_to_deliver'      => 5,
            default                 => 99,
        };
    }

    private function notifyAdmins(string $title, string $body): void
    {
        $admins = User::where('role', 'admin')->get();

        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->sendToDatabase($admins);
    }
}
