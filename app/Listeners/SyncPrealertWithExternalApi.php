<?php

namespace App\Listeners;

use App\Events\PackagePrealerted;
use App\Models\User;
use App\Services\MlcLogisticsClient;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SyncPrealertWithExternalApi implements ShouldQueue
{
    public function __construct(private readonly MlcLogisticsClient $mlc)
    {
    }

    public function handle(PackagePrealerted $event): void
    {
        $package = $event->package;

        try {
            // La API exige un array aunque sea un solo registro, y falla (500) si se
            // manda "retener"/"estatus" en false explícito — se omiten para que la
            // API los defaultee a 0/null, confirmado en vivo.
            $response = $this->mlc->http()->post('/prealertas', [[
                'codigo' => config('services.mlclogistics.default_codigo'),
                'numero_seguimiento' => $package->tracking,
                'fecha' => now()->toDateString(),
                'tipo_envio' => 'aereo',
                'observaciones' => $package->description,
            ]]);

            if ($response->successful() && $response->json('success') === true) {
                return;
            }

            Log::warning('SyncPrealertWithExternalApi: respuesta inesperada', [
                'tracking' => $package->tracking,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            $this->notifyAdmins(
                title: 'Error al registrar prealerta',
                body: "Paquete {$package->tracking}: respuesta {$response->status()} de la API",
            );
        } catch (\Throwable $e) {
            Log::error('SyncPrealertWithExternalApi: excepción al llamar API', [
                'tracking' => $package->tracking,
                'error'    => $e->getMessage(),
            ]);

            $this->notifyAdmins(
                title: 'Error al registrar prealerta',
                body: "Paquete {$package->tracking}: {$e->getMessage()}",
            );
        }
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
