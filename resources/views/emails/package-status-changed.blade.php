<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1d4ed8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .status-row { display: flex; align-items: center; gap: 12px; margin: 20px 0; }
        .status-badge { background-color: #dbeafe; color: #1d4ed8; padding: 6px 12px; border-radius: 9999px; font-weight: bold; font-size: 14px; }
        .arrow { color: #6b7280; font-size: 20px; }
        .status-badge.new { background-color: #dcfce7; color: #15803d; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">Tu paquete actualizó su estado</h2>
    </div>

    <div class="body">
        <p>Hola {{ $package->user->name }},</p>
        <p>Tu paquete con tracking <strong>{{ $package->tracking }}</strong> cambió de estado:</p>

        <div class="status-row">
            <span class="status-badge">{{ $fromStatus }}</span>
            <span class="arrow">→</span>
            <span class="status-badge new">{{ $toStatus }}</span>
        </div>

        @if ($package->shelf_location)
            <div class="field">
                <div class="label">Ubicación en estante</div>
                <div class="value">{{ $package->shelf_location }}</div>
            </div>
        @endif

        <div class="field">
            <div class="label">Tracking</div>
            <div class="value">{{ $package->tracking }}</div>
        </div>

        <div class="field">
            <div class="label">Tu casillero</div>
            <div class="value">{{ $package->user->locker_code }}</div>
        </div>

        <div class="footer">
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
