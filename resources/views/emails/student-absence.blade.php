<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Aviso de Inasistencia</title>
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e4e4e7;
        }
        .header {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .content {
            padding: 24px;
            line-height: 1.6;
        }
        .alert-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px 16px;
            margin: 16px 0;
            border-radius: 4px;
        }
        .info-table {
            width: 100%;
            margin: 16px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f4f4f5;
        }
        .info-table td.label {
            font-weight: 600;
            color: #71717a;
            width: 35%;
        }
        .footer {
            background-color: #fafafa;
            padding: 16px 24px;
            font-size: 12px;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px solid #f4f4f5;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ $school->name }}</h1>
        </div>
        <div class="content">
            <p>Estimado(a) tutor(a) <strong>{{ $guardian->full_name }}</strong>,</p>

            <div class="alert-box">
                Le informamos que el día de hoy se registró una <strong>inasistencia</strong> para su hijo(a) o tutorado(a).
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Alumno:</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Fecha:</td>
                    <td>{{ $session->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Materia:</td>
                    <td>{{ $session->schoolClass?->subject?->name ?? 'Clase programada' }}</td>
                </tr>
                <tr>
                    <td class="label">Horario:</td>
                    <td>{{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }} hrs</td>
                </tr>
            </table>

            <p style="font-size: 13px; color: #52525b;">
                Si la inasistencia fue justificada o si tiene alguna duda, le solicitamos acudir al área de Control Escolar de la institución para presentar el comprobante médico o motivo correspondiente.
            </p>
        </div>
        <div class="footer">
            Este es un aviso automático generado por el Sistema de Control Escolar de {{ $school->name }}. Por favor no responda a este mensaje.
        </div>
    </div>
</body>
</html>

