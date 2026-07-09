<?php

namespace App\Console\Commands;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use App\Services\DbService\PackageService;
use App\Services\MlcLogisticsClient;
use App\Support\Weight;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPackageStatusesFromApi extends Command
{
    protected $signature = 'packages:sync-statuses';

    protected $description = 'Sincroniza estados de paquetes consultando la API externa de mlclogistics';

    private const API_STATE_MAP = [
        'Manifiesto Programado' => PackageStatus::ASSIGNED_FLIGHT,
        'En Transito a CR'      => PackageStatus::IN_TRANSIT,
        'En Aduanas CR'         => PackageStatus::RECEIVED_IN_CUSTOMS,
        'En Oficina Central'    => PackageStatus::CUSTOMS_PROCESS_FINISHED,
        'Entregado'             => PackageStatus::RECEIVED_IN_BUSINESS,
    ];

    private const IGNORED_API_STATUSES = [
        'Entrega Programada',
        'En ruta',
    ];

    private const SYNCABLE_STATUSES = [
        PackageStatus::PREALERTED->value,
        PackageStatus::RECEIVED_IN_WAREHOUSE->value,
        PackageStatus::ASSIGNED_FLIGHT->value,
        PackageStatus::IN_TRANSIT->value,
        PackageStatus::RECEIVED_IN_CUSTOMS->value,
        PackageStatus::CUSTOMS_PROCESS_FINISHED->value,
    ];

    public function handle(PackageService $packageService, MlcLogisticsClient $mlc): int
    {
        $this->info('Consultando API externa...');

        try {
            $prealertas = $this->fetchAllPages($mlc, '/prealertas', 'prealertas');
            $paquetes = $this->fetchAllPages($mlc, '/paquetes', 'paquetes');
        } catch (\Throwable $e) {
            Log::error('SyncPackageStatusesFromApi: error al llamar API', ['error' => $e->getMessage()]);
            $this->notifyAdmins('Error al sincronizar estados', "No se pudo conectar con la API: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Build lookups: tracking (uppercase) => item
        $prealertasLookup = [];
        foreach ($prealertas as $item) {
            if (isset($item['numero_seguimiento'])) {
                $prealertasLookup[strtoupper($item['numero_seguimiento'])] = $item;
            }
        }

        $paquetesLookup = [];
        foreach ($paquetes as $item) {
            if (isset($item['tracking'])) {
                $paquetesLookup[strtoupper($item['tracking'])] = $item;
            }
        }

        $this->info('Prealertas en API: ' . count($prealertasLookup));
        $this->info('Paquetes en API: ' . count($paquetesLookup));

        $packages = Package::whereIn('status', self::SYNCABLE_STATUSES)->get();

        $this->info("Paquetes a revisar: {$packages->count()}");

        $updated = 0;
        $skipped = 0;

        foreach ($packages as $package) {
            $tracking = strtoupper($package->tracking);

            // Paquete ya recibido físicamente en bodega: la API de paquetes manda.
            if (isset($paquetesLookup[$tracking])) {
                if ($this->syncFromPaquete($package, $paquetesLookup[$tracking], $packageService)) {
                    $updated++;
                } else {
                    $skipped++;
                }

                continue;
            }

            // Todavía no hay paquete físico: solo puede avanzar de prealertado a recibido en bodega.
            if ($package->status === PackageStatus::PREALERTED->value && isset($prealertasLookup[$tracking])) {
                if ($this->syncFromPrealerta($package, $prealertasLookup[$tracking], $packageService)) {
                    $updated++;
                } else {
                    $skipped++;
                }

                continue;
            }

            $skipped++; // Not in API yet
        }

        $this->info("Actualizados: {$updated} | Omitidos: {$skipped}");

        return self::SUCCESS;
    }

    private function syncFromPaquete(Package $package, array $apiItem, PackageService $packageService): bool
    {
        $apiEstado = $apiItem['estatus'] ?? null;

        if (in_array($apiEstado, self::IGNORED_API_STATUSES, true)) {
            return false;
        }

        if (is_string($apiEstado) && str_starts_with($apiEstado, 'Recibido en')) {
            // El nombre de la bodega varía ("Recibido en San José", "Recibido en {bodega}", etc.)
            $targetStatus = PackageStatus::RECEIVED_IN_WAREHOUSE;
        } elseif (isset(self::API_STATE_MAP[$apiEstado])) {
            $targetStatus = self::API_STATE_MAP[$apiEstado];
        } else {
            Log::warning('SyncPackageStatusesFromApi: estado desconocido en API', [
                'tracking' => $package->tracking,
                'estado'   => $apiEstado,
            ]);

            return false;
        }

        if ($this->ordinal($targetStatus->value) <= $this->ordinal($package->status)) {
            return false; // Manual change was ahead or same state — respect it
        }

        try {
            $weight = $apiItem['peso_real'] ?? $apiItem['peso'] ?? null;
            $this->advanceToTarget($package, $targetStatus, $weight, $packageService);

            return true;
        } catch (\Throwable $e) {
            Log::warning('SyncPackageStatusesFromApi: error al actualizar paquete', [
                'tracking' => $package->tracking,
                'error'    => $e->getMessage(),
            ]);
            $this->notifyAdmins(
                'Error al sincronizar estado de paquete',
                "Paquete {$package->tracking}: {$e->getMessage()}"
            );

            return false;
        }
    }

    private function syncFromPrealerta(Package $package, array $apiItem, PackageService $packageService): bool
    {
        if ((int) ($apiItem['estatus'] ?? 0) !== 1) {
            return false; // Sigue prealertado del lado de MLC
        }

        try {
            $packageService->updatePackageStatus(
                package: $package,
                newStatus: PackageStatus::RECEIVED_IN_WAREHOUSE->value,
                changedBy: null,
                note: 'Sincronizado automáticamente vía API',
                shelfLocation: null,
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('SyncPackageStatusesFromApi: error al actualizar paquete', [
                'tracking' => $package->tracking,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
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

            // Update weight before transitioning to RECEIVED_IN_WAREHOUSE (API sends lbs; store grams)
            if ($nextStep === PackageStatus::RECEIVED_IN_WAREHOUSE && $pesoFinal !== null) {
                $package->update(['weight' => Weight::lbsToGrams((float) $pesoFinal)]);
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

    private function fetchAllPages(MlcLogisticsClient $mlc, string $path, string $itemsKey): array
    {
        $items = [];
        $page = 1;

        do {
            $body = $mlc->http()->get($path, ['page' => $page])->throw()->json();

            $items = array_merge($items, $body[$itemsKey] ?? []);
            $pageCount = $body['pagination']['pageCount'] ?? 1;
            $page++;
        } while ($page <= $pageCount);

        return $items;
    }

    private function ordinal(string $status): int
    {
        return match ($status) {
            'prealerted'            => 0,
            'received_in_warehouse' => 1,
            'assigned_flight'       => 2,
            'in_transit'            => 3,
            'received_in_customs'      => 4,
            'customs_process_finished' => 5,
            'received_in_business'     => 6,
            'ready_to_deliver'         => 7,
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
