<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Velora\Models\TeamInvitation;

class TeamMemberJoinedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TeamInvitation $invitation,
        public Model $joinedUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New team member: '.$this->invitation->team->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'velora::mail.member-joined',
        );
    }
}
