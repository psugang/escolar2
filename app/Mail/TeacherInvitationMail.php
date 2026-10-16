<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(
        public Invitation $invitation,
        #[\SensitiveParameter]
        public string $plainToken
    ) {
        $this->acceptUrl = route('invitations.accept', ['token' => $this->plainToken]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitación para integrarse como Docente - {$this->invitation->school->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->acceptUrl,
                'school' => $this->invitation->school,
            ],
        );
    }
}
