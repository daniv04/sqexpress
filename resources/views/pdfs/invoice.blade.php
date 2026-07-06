<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 32px 40px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .header-table td { vertical-align: top; padding: 4px; }
        .company-name { font-size: 20px; font-weight: bold; color: #1d4ed8; margin-bottom: 4px; }
        .company-info { font-size: 11px; color: #555; line-height: 1.6; }
        .invoice-label { font-size: 24px; font-weight: bold; color: #1d4ed8; text-align: right; }
        .invoice-meta { text-align: right; font-size: 11px; color: #555; line-height: 1.8; margin-top: 6px; }
        .invoice-meta strong { color: #333; }
        .section-title { font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .client-box { background-color: #f3f4f6; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; }
        .client-box table { width: 100%; border-collapse: collapse; }
        .client-box td { font-size: 12px; padding: 2px 0; }
        .client-box td:first-child { color: #6b7280; width: 120px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #1d4ed8; color: white; padding: 6px 10px; text-align: left; font-size: 11px; }
        .items-table th.right { text-align: right; }
        .items-table td { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: middle; white-space: nowrap; }
        .items-table td.right { text-align: right; }
        .items-table tr:nth-child(even) { background-color: #f9fafb; }
        .items-table tr:last-child td { border-bottom: none; }
        .type-badge { display: inline-block; background-color: #dbeafe; color: #1d4ed8; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .totals-table { width: 280px; margin-left: auto; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; font-size: 12px; }
        .totals-table td:last-child { text-align: right; }
        .totals-table tr.total { background-color: #1d4ed8; color: white; font-weight: bold; font-size: 14px; }
        .footer-note { font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 10px; }
        .divider { border: none; border-top: 2px solid #1d4ed8; margin: 16px 0; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                @php $logoPath = public_path('images/logo_sqexpress_noback.png'); @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="SQExpress" style="height: 50px; margin-bottom: 8px;"><br>
                @endif
                <div class="company-name">SQExpress</div>
                <div class="company-info">
                    Servicio de Paquetería Internacional<br>
                    Costa Rica<br>
                    Tel: +506 0000-0000<br>
                    info@sqexpress.com · sqexpress.com
                </div>
            </td>
            <td style="width: 50%;">
                <div class="invoice-label">FACTURA</div>
                <div class="invoice-meta">
                    <strong>N.°</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Fecha:</strong> {{ $invoice->generated_at->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- CLIENT INFO --}}
    <div class="section-title">Datos del cliente</div>
    <div class="client-box">
        <table>
            <tr>
                <td>Nombre:</td>
                <td><strong>{{ $invoice->user->name }}</strong></td>
            </tr>
            <tr>
                <td>Correo:</td>
                <td>{{ $invoice->user->email }}</td>
            </tr>
            @if($invoice->user->phone)
            <tr>
                <td>Teléfono:</td>
                <td>{{ $invoice->user->phone }}</td>
            </tr>
            @endif
            @if($invoice->user->address)
            <tr>
                <td>Dirección:</td>
                <td>{{ $invoice->user->address }}</td>
            </tr>
            @endif
            @if($invoice->user->distrito || $invoice->user->canton || $invoice->user->provincia)
            <tr>
                <td>Ubicación:</td>
                <td>
                    {{ $invoice->user->distrito?->name }}
                    @if($invoice->user->canton), {{ $invoice->user->canton->name }}@endif
                    @if($invoice->user->provincia), {{ $invoice->user->provincia->name }}@endif
                </td>
            </tr>
            @endif
            <tr>
                <td>Casillero:</td>
                <td><strong>{{ $invoice->user->locker_code }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Tracking</th>
                <th>Tipo</th>
                <th class="right" style="width: 90px;">Peso</th>
                <th class="right" style="width: 110px;">Precio por unidad</th>
                <th class="right" style="width: 110px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->packages as $package)
            <tr>
                <td>{{ $package->tracking }}</td>
                <td><span class="type-badge">{{ $package->shippingMethod?->name ?? 'N/A' }}</span></td>
                <td class="right">{{ $package->weight ? number_format($package->weight, 2) . ' Kg' : '—' }}</td>
                <td class="right">₡{{ number_format($package->service_cost, 2) }}</td>
                <td class="right">₡{{ number_format($package->service_cost, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table class="totals-table">
        <tr>
            <td style="color: #6b7280;">Subtotal:</td>
            <td>₡{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td style="color: #15803d;">Descuento (10% — cliente nuevo):</td>
            <td style="color: #15803d;">- ₡{{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->delivery_fee > 0)
        <tr>
            <td style="color: #6b7280;">Cargo por entrega:</td>
            <td>₡{{ number_format($invoice->delivery_fee, 2) }}</td>
        </tr>
        @endif
        <tr class="total">
            <td>Total:</td>
            <td>₡{{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer-note">
        * Los puntos obtenidos son el 1% del total a pagar ({{ $invoice->points_earned }} puntos).<br>
        @if($invoice->discount_amount > 0)
        * Descuento del 10% aplicado por ser tu primera factura con SQExpress.<br>
        @endif
        Este documento es una factura generada automáticamente. Para consultas escriba a info@sqexpress.com.
    </div>

</body>
</html>
