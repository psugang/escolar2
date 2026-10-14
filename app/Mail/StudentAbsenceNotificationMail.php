<?php

namespace App\Mail;

use App\Models\ClassSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentAbsenceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public Guardian $guardian,
        public ClassSession $session,
        public School $school
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Aviso de Inasistencia Escolar - {$this->student->full_name} ({$this->school->name})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-absence',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
