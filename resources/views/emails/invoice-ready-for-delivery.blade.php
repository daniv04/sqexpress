<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #15803d; color: white; padding: 20px; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .invoice-number { font-family: monospace; font-size: 18px; font-weight: bold; color: #1d4ed8; margin: 12px 0; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
        .package-item { background-color: #f9fafb; padding: 12px; border-radius: 4px; margin: 8px 0; }
        .package-description { color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div style="background:#ffffff; padding:16px 20px; border-radius:8px 8px 0 0; border:1px solid #e5e7eb; border-bottom:none; text-align:left;">
        <img src="https://i.imgur.com/aSeqoim.png" alt="SQ EXPRESS CR" style="height:80px; display:block;">
    </div>
    <div class="header">
        <h2 style="margin:0;">¡Tus paquetes están listos para entregar!</h2>
    </div>

    <div class="body">
        <p>Hola {{ $invoice->user->name }},</p>
        <p>Los siguientes paquetes ya están listos para ser entregados o retirados en nuestra oficina. Adjuntamos la factura correspondiente.</p>

        <div class="invoice-number">{{ $invoice->invoice_number }}</div>

        <div style="margin: 16px 0;">
            <div class="label">Paquetes</div>
            @foreach($invoice->packages as $package)
            <div class="package-item">
                <strong>{{ $package->tracking }}</strong><br>
                <span class="package-description">{{ $package->description ?: '(sin descripción)' }}</span>
            </div>
            @endforeach
        </div>

        <div class="field">
            <div class="label">Total</div>
            <div class="value">${{ number_format($invoice->total, 2) }}</div>
        </div>

        @if($invoice->total_crc !== null)
        <div class="field">
            <div class="label">Total a pagar (CRC)</div>
            <div class="value" style="color: #1d4ed8; font-size: 18px;">₡{{ number_format($invoice->total_crc, 2) }}</div>
        </div>
        @endif

        <div class="field">
            <div class="label">Estado de pago</div>
            <div class="value" style="color: {{ $invoice->isPaid() ? '#15803d' : '#b45309' }};">
                {{ $invoice->isPaid() ? 'Cancelada' : 'Pendiente de pago' }}
            </div>
        </div>

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
