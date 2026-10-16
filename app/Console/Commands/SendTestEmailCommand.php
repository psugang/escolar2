<?php

namespace App\Console\Commands;

use App\Mail\TestEmail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

#[Signature('mail:test {recipient : Dirección de correo electrónico de prueba}')]
#[Description('Envía un correo de prueba transaccional para verificar la conexión SMTP/Brevo')]
class SendTestEmailCommand extends Command
{
    public function handle(): int
    {
        $recipient = (string) $this->argument('recipient');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("La dirección de correo '{$recipient}' no tiene un formato válido.");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $port = (string) config('mail.mailers.smtp.port');
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        $this->info('Iniciando prueba de correo transaccional...');
        $this->table(
            ['Parámetro', 'Valor Configurado'],
            [
                ['Mailer por defecto', $mailer],
                ['Servidor SMTP (Host)', $host],
                ['Puerto', $port],
                ['Remitente (From Address)', $fromAddress],
                ['Nombre Remitente (From Name)', $fromName],
                ['Destinatario de prueba', $recipient],
            ]
        );

        try {
            $sentAt = now()->format('d/m/Y H:i:s T');
            Mail::to($recipient)->send(new TestEmail($recipient, $sentAt));

            $this->newLine();
            $this->info("✓ El mensaje de prueba fue enviado y aceptado por el transporte '{$mailer}'.");
            $this->line('<comment>Nota:</comment> La aceptación por el servidor SMTP confirma la conexión y autenticación.');
            $this->line('La entrega efectiva a la bandeja de entrada depende de que el remitente esté verificado en Brevo y de las políticas SPF/DKIM/DMARC del dominio.');

            return self::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $this->newLine();
            $this->error('Error de transporte SMTP al intentar enviar el correo:');
            $this->line('<fg=red>Detalle:</> '.$e->getMessage());
            $this->line('<comment>Sugerencia:</comment> Verifica en tu archivo .env que MAIL_HOST, MAIL_PORT, MAIL_USERNAME y MAIL_PASSWORD sean válidos.');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Ocurrió un error inesperado al procesar el envío:');
            $this->line('<fg=red>Detalle:</> '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
