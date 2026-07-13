<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;

class TeamMemberJoinedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public readonly string $roleLabel;

    public function __construct(
        public TeamInvitation $invitation,
        public Model $joinedUser,
    ) {
        $this->roleLabel = $this->resolveRoleLabel();
    }

    public function envelope(): Envelope
    {
        $subject = (string) config('velora.mail.member_joined_subject', 'Novi suradnik organizacije: :team');

        return new Envelope(
            subject: __($subject, ['team' => $this->invitation->team->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: (string) config('velora.mail.member_joined_view', 'velora::mail.member-joined'),
        );
    }

    private function resolveRoleLabel(): string
    {
        $roleSlug = trim((string) $this->invitation->role_slug);

        if ($roleSlug === '') {
            return '';
        }

        $role = Role::query()
            ->availableToTeam($this->invitation->team_id)
            ->where('slug', $roleSlug)
            ->orderByRaw('case when team_id = ? then 0 else 1 end', [$this->invitation->team_id])
            ->first(['label', 'name']);

        $roleLabel = $role?->label ?: $role?->name;

        if (! $roleLabel) {
            $configuredRole = collect(config('velora.system_roles', []))->firstWhere('slug', $roleSlug);
            $roleLabel = data_get($configuredRole, 'label') ?: data_get($configuredRole, 'name');
        }

        return (string) ($roleLabel ?: Str::headline($roleSlug));
    }
}
