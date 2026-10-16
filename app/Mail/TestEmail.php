<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public string $sentAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prueba de Correo Transaccional - '.config('app.name', 'Sistema Escolar'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
            with: [
                'recipientEmail' => $this->recipientEmail,
                'sentAt' => $this->sentAt,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
            ],
        );
    }
}
