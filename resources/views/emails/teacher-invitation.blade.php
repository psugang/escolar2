<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Invitación Docente</title>
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
            max-width: 580px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e4e4e7;
        }
        .header {
            background-color: #18181b;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 19px;
            font-weight: 600;
        }
        .content {
            padding: 28px 24px;
            line-height: 1.6;
        }
        .info-table {
            width: 100%;
            margin: 16px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f4f4f5;
            font-size: 14px;
        }
        .info-table td.label {
            font-weight: 600;
            color: #71717a;
            width: 35%;
        }
        .button-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            background-color: #18181b;
            color: #ffffff !important;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }
        .footer {
            background-color: #fafafa;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #71717a;
            border-top: 1px solid #e4e4e7;
        }
        .note {
            font-size: 12px;
            color: #71717a;
            margin-top: 20px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Sistema de Control Escolar</h1>
        </div>
        <div class="content">
            <p>Estimado(a) <strong>{{ $invitation->full_name }}</strong>,</p>
            <p>Has recibido una invitación para registrarte y activar tu cuenta como <strong>Profesor</strong> en el plantel:</p>

            <table class="info-table">
                <tr>
                    <td class="label">Plantel Escolar:</td>
                    <td><strong>{{ $school->name }}</strong></td>
                </tr>
                @if ($school->code)
                <tr>
                    <td class="label">Clave C.C.T.:</td>
                    <td>{{ $school->code }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Correo Asignado:</td>
                    <td>{{ $invitation->email }}</td>
                </tr>
                <tr>
                    <td class="label">Vigencia del Enlace:</td>
                    <td>Hasta el {{ $invitation->expires_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <p>Para activar tu cuenta y establecer tu contraseña personal de acceso, haz clic en el siguiente botón:</p>

            <div class="button-wrapper">
                <a href="{{ $acceptUrl }}" class="btn">Activar mi Cuenta de Docente</a>
            </div>

            <p class="note">
                Por motivos de seguridad, este enlace es personal, intransferible y de un solo uso. Si no puedes hacer clic en el botón, copia y pega la siguiente dirección en tu navegador:<br>
                <a href="{{ $acceptUrl }}" style="color: #2563eb;">{{ $acceptUrl }}</a>
            </p>
        </div>
        <div class="footer">
            Este mensaje fue enviado automáticamente por el sistema de control escolar para {{ $school->name }}. Si no reconoces esta invitación, puedes ignorar este mensaje.
        </div>
    </div>
</body>
</html>
