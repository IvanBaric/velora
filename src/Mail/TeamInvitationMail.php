<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Velora\Models\TeamInvitation;

class TeamInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TeamInvitation $invitation,
        public string $url,
        public string $roleLabel,
    ) {}

    public function envelope(): Envelope
    {
        $subject = (string) config('velora.mail.invitation_subject', 'Poziv za suradnju: :team');

        return new Envelope(
            subject: __($subject, ['team' => $this->invitation->team->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: (string) config('velora.mail.invitation_view', 'velora::mail.invitation'),
        );
    }
}
