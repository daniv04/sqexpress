<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1d4ed8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .change-row { display: flex; align-items: center; gap: 12px; margin: 12px 0; }
        .status-badge { background-color: #dbeafe; color: #1d4ed8; padding: 6px 12px; border-radius: 9999px; font-weight: bold; font-size: 14px; }
        .arrow { color: #6b7280; font-size: 20px; }
        .status-badge.new { background-color: #dcfce7; color: #15803d; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div style="background:#ffffff; padding:16px 20px; border-radius:8px 8px 0 0; border:1px solid #e5e7eb; border-bottom:none; text-align:left;">
        <img src="https://i.imgur.com/aSeqoim.png" alt="SQ EXPRESS CR" style="height:48px; display:block;">
    </div>
    <div class="header" style="border-radius:0;">
        <h2 style="margin:0;">Actualizamos la información de tu paquete</h2>
    </div>

    <div class="body">
        <p>Hola {{ $package->user->name }},</p>
        <p>Un administrador actualizó los siguientes datos de tu paquete con tracking <strong>{{ $package->tracking }}</strong>:</p>

        @foreach ($changes as $field => $change)
            <div class="field">
                <div class="label">
                    @switch($field)
                        @case('tracking') Tracking @break
                        @case('shipping_method_id') Método de envío @break
                        @case('description') Descripción @break
                        @case('weight') Peso (kg) @break
                        @case('approx_value') Valor aprox. (USD) @break
                        @case('shelf_location') Estante @break
                        @default {{ $field }}
                    @endswitch
                </div>
                <div class="change-row">
                    @if ($field === 'shipping_method_id')
                        <span class="status-badge">{{ \App\Models\ShippingMethod::find($change['old'])?->name ?? $change['old'] }}</span>
                        <span class="arrow">→</span>
                        <span class="status-badge new">{{ $package->shippingMethod->name }}</span>
                    @else
                        <span class="status-badge">{{ $change['old'] }}</span>
                        <span class="arrow">→</span>
                        <span class="status-badge new">{{ $change['new'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="footer">
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
