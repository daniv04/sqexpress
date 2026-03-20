<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1d4ed8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .invoice-number { font-family: monospace; font-size: 18px; font-weight: bold; color: #1d4ed8; margin: 12px 0; }
        .points-badge { display: inline-block; background-color: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 9999px; font-weight: bold; font-size: 14px; margin: 12px 0; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">Tu factura está lista</h2>
    </div>

    <div class="body">
        <p>Hola {{ $package->user->name }},</p>
        <p>Tu factura está adjunta a este correo.</p>

        <div class="invoice-number">{{ $package->invoice_number }}</div>

        <p>Ganaste <strong>{{ $package->points_earned }} puntos</strong> con este envío.</p>

        @if($package->discount_amount > 0)
        <div style="background-color: #dcfce7; color: #15803d; padding: 10px 16px; border-radius: 6px; margin: 12px 0; font-weight: bold;">
            🎉 ¡Descuento del 10% aplicado por ser tu primera factura con SQExpress! Ahorraste ₡{{ number_format($package->discount_amount, 2) }}.
        </div>
        @endif

        <div class="field">
            <div class="label">Tracking</div>
            <div class="value">{{ $package->tracking }}</div>
        </div>

        @if($package->discount_amount > 0)
        <div class="field">
            <div class="label">Subtotal</div>
            <div class="value">₡{{ number_format($package->service_cost, 2) }}</div>
        </div>
        <div class="field">
            <div class="label">Descuento (10%)</div>
            <div class="value" style="color: #15803d;">- ₡{{ number_format($package->discount_amount, 2) }}</div>
        </div>
        @else
        <div class="field">
            <div class="label">Costo del servicio</div>
            <div class="value">₡{{ number_format($package->service_cost, 2) }}</div>
        </div>
        @endif
        @if($package->delivery_fee > 0)
        <div class="field">
            <div class="label">Cargo por entrega</div>
            <div class="value">₡{{ number_format($package->delivery_fee, 2) }}</div>
        </div>
        @endif
        <div class="field">
            <div class="label">Total a pagar</div>
            <div class="value">₡{{ number_format($package->service_cost - $package->discount_amount + $package->delivery_fee, 2) }}</div>
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
