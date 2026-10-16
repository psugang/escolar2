<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Prueba de Correo Transaccional</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            margin: 0;
            padding: 24px;
        }
        .card {
            background-color: #ffffff;
            max-width: 560px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e4e4e7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .header {
            background-color: #18181b;
            color: #ffffff;
            padding: 20px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .content {
            padding: 24px;
            line-height: 1.6;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #047857;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .info-table {
            width: 100%;
            margin: 16px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f4f4f5;
            font-size: 13px;
        }
        .info-table td.label {
            color: #71717a;
            font-weight: 600;
            width: 40%;
        }
        .footer {
            background-color: #fafafa;
            padding: 14px 24px;
            text-align: center;
            font-size: 12px;
            color: #71717a;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ config('app.name', 'Sistema Escolar') }}</h1>
        </div>
        <div class="content">
            <span class="badge">✓ Conexión SMTP Exitosa</span>
            <p>Este es un correo de prueba emitido para verificar la integración de correo transaccional (Brevo / SMTP) en la aplicación.</p>

            <table class="info-table">
                <tr>
                    <td class="label">Destinatario:</td>
                    <td><strong>{{ $recipientEmail }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Transporte Activo:</td>
                    <td><code>{{ $mailer }}</code></td>
                </tr>
                <tr>
                    <td class="label">Servidor / Host:</td>
                    <td><code>{{ $host }}:{{ $port }}</code></td>
                </tr>
                <tr>
                    <td class="label">Marca de Tiempo:</td>
                    <td>{{ $sentAt }}</td>
                </tr>
            </table>

            <p style="color: #71717a; font-size: 13px;">
                Si estás visualizando este mensaje en tu bandeja de entrada, la negociación TLS, autenticación y entrega del proveedor están funcionando correctamente.
            </p>
        </div>
        <div class="footer">
            Mensaje de diagnóstico generado automáticamente por el comando <code>php artisan mail:test</code>.
        </div>
    </div>
</body>
</html>
