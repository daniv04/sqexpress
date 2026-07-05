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
    <div style="background:#ffffff; padding:16px 20px; border-radius:8px 8px 0 0; border:1px solid #e5e7eb; border-bottom:none; text-align:left;">
        <img src="https://i.imgur.com/aSeqoim.png" alt="SQExpress" style="height:48px; display:block;">
    </div>
    <div class="header" style="border-radius:0;">
        <h2 style="margin:0;">Tu paquete fue eliminado</h2>
    </div>

    <div class="body">
        <p>Hola {{ $userName }},</p>
        <p>Te confirmamos que el paquete con tracking <strong>{{ $tracking }}</strong> fue eliminado de tu cuenta por un administrador.</p>

        @if ($description)
            <div class="field">
                <div class="label">Descripción</div>
                <div class="value">{{ $description }}</div>
            </div>
        @endif

        <div class="footer">
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
