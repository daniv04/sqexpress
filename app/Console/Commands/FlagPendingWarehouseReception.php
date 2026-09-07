<?php

namespace App\Console\Commands;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Services\DbService\PackageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlagPendingWarehouseReception extends Command
{
    protected $signature = 'packages:flag-pending-warehouse-reception';

    protected $description = 'Marca como "pendiente de recepción en bodega" los paquetes liberados de aduana hace más de 24 horas sin confirmarse su llegada física a la oficina';

    private const STALE_AFTER_HOURS = 24;

    public function handle(PackageService $packageService): int
    {
        $threshold = now()->subHours(self::STALE_AFTER_HOURS);

        $packages = Package::where('status', PackageStatus::CUSTOMS_PROCESS_FINISHED->value)
            ->with('statusHistories')
            ->get();

        $flagged = 0;

        foreach ($packages as $package) {
            $enteredAt = $package->statusHistories
                ->where('to_status', PackageStatus::CUSTOMS_PROCESS_FINISHED->value)
                ->sortByDesc('created_at')
                ->first()
                ?->created_at;

            if (! $enteredAt || $enteredAt->gt($threshold)) {
                continue;
            }

            try {
                $packageService->updatePackageStatus(
                    package: $package,
                    newStatus: PackageStatus::PENDING_WAREHOUSE_RECEPTION->value,
                    changedBy: null,
                    note: 'Transición automática: más de '.self::STALE_AFTER_HOURS.' horas liberado de aduana sin confirmarse la recepción física en bodega.',
                );

                $flagged++;
            } catch (\Throwable $e) {
                Log::warning('FlagPendingWarehouseReception: error al actualizar paquete', [
                    'tracking' => $package->tracking,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Paquetes marcados como pendientes de recepción: {$flagged}");

        return self::SUCCESS;
    }
}
