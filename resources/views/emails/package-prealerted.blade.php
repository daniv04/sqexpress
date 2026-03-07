<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1d4ed8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">Prealerta recibida</h2>
    </div>

    <div class="body">
        <p>Hola {{ $package->user->name }},</p>
        <p>Recibimos tu prealerta. Cuando tu paquete llegue a nuestra bodega te notificaremos.</p>

        <div class="field">
            <div class="label">Tracking</div>
            <div class="value">{{ $package->tracking }}</div>
        </div>

        <div class="field">
            <div class="label">Descripción</div>
            <div class="value">{{ $package->description }}</div>
        </div>

        <div class="field">
            <div class="label">Método de envío</div>
            <div class="value">{{ $package->shippingMethod->name }}</div>
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
