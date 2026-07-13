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
        .package-item { background-color: #f9fafb; padding: 12px; border-radius: 4px; margin: 8px 0; }
        .package-tracking { font-family: monospace; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div style="background:#ffffff; padding:16px 20px; border-radius:8px 8px 0 0; border:1px solid #e5e7eb; border-bottom:none; text-align:left;">
        <img src="https://i.imgur.com/aSeqoim.png" alt="SQ EXPRESS CR" style="height:80px; display:block;">
    </div>
    <div class="header" style="border-radius:0;">
        <h2 style="margin:0;">Tu factura está lista</h2>
    </div>

    <div class="body">
        <p>Hola {{ $invoice->user->name }},</p>
        <p>Tu factura está adjunta a este correo.</p>

        <div class="invoice-number">{{ $invoice->invoice_number }}</div>

        <p>Ganaste <strong>{{ $invoice->points_earned }} puntos</strong> con esta factura.</p>

        @if($invoice->discount_amount > 0)
        <div style="background-color: #dcfce7; color: #15803d; padding: 10px 16px; border-radius: 6px; margin: 12px 0; font-weight: bold;">
            🎉 ¡Descuento del 10% aplicado por ser tu primera factura con SQ EXPRESS CR! Ahorraste ${{ number_format($invoice->discount_amount, 2) }}.
        </div>
        @endif

        @if($invoice->packages->count() > 1)
        <div style="margin: 16px 0;">
            <div class="label">Paquetes incluidos en esta factura</div>
            @foreach($invoice->packages as $package)
            <div class="package-item">
                <strong>{{ $package->tracking }}</strong><br>
                <span class="package-tracking">{{ $package->description ?: '(sin descripción)' }}</span><br>
                <span style="color: #1d4ed8; font-weight: bold;">${{ number_format($package->service_cost, 2) }}</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="field">
            <div class="label">Tracking</div>
            <div class="value">{{ $invoice->packages->first()->tracking }}</div>
        </div>
        @endif

        @if($invoice->discount_amount > 0)
        <div class="field">
            <div class="label">Subtotal</div>
            <div class="value">${{ number_format($invoice->subtotal, 2) }}</div>
        </div>
        <div class="field">
            <div class="label">Descuento (10%)</div>
            <div class="value" style="color: #15803d;">- ${{ number_format($invoice->discount_amount, 2) }}</div>
        </div>
        @else
        <div class="field">
            <div class="label">Subtotal</div>
            <div class="value">${{ number_format($invoice->subtotal, 2) }}</div>
        </div>
        @endif
        @if($invoice->delivery_fee > 0)
        <div class="field">
            <div class="label">Cargo por entrega</div>
            <div class="value">${{ number_format($invoice->delivery_fee, 2) }}</div>
        </div>
        @endif
        <div class="field">
            <div class="label">Total a pagar</div>
            <div class="value">${{ number_format($invoice->total, 2) }}</div>
        </div>

        @if($invoice->exchange_rate !== null)
        <div class="field">
            <div class="label">Tipo de cambio</div>
            <div class="value">₡{{ number_format($invoice->exchange_rate, 2) }}</div>
        </div>
        <div class="field">
            <div class="label">Total a pagar (CRC)</div>
            <div class="value" style="color: #1d4ed8; font-size: 18px;">₡{{ number_format($invoice->total_crc, 2) }}</div>
        </div>
        @endif

        <div class="field">
            <div class="label">Tu casillero</div>
            <div class="value">{{ $invoice->user->locker_code }}</div>
        </div>

        <div class="footer">
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
