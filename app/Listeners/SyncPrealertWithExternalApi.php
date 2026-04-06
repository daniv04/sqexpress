<?php

namespace App\Listeners;

use App\Events\PackagePrealerted;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPrealertWithExternalApi implements ShouldQueue
{
    private const API_URL = 'https://logis-tico.com/api';

    private const EXPECTED_MESSAGES = [
        'Ya existe ese numero de seguimiento',
        'Seguimiento registrado correctamente',
    ];

    public function handle(PackagePrealerted $event): void
    {
        $package = $event->package;

        try {
            $response = Http::timeout(15)->post(self::API_URL, [
                'action'      => 'createPreAlertSQ',
                'descripcion' => $package->description,
                'seguimiento' => $package->tracking,
            ]);

            dd($response->body());

            $mensaje = $body['mensaje'] ?? null;

            if (in_array($mensaje, self::EXPECTED_MESSAGES, true)) {
                return;
            }

            $errorText = $mensaje ?? $response->body();

            Log::warning('SyncPrealertWithExternalApi: respuesta inesperada', [
                'tracking' => $package->tracking,
                'status'   => $response->status(),
                'body'     => $errorText,
            ]);

            $this->notifyAdmins(
                title: 'Error al registrar prealerta',
                body: "Paquete {$package->tracking}: {$errorText}",
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
