<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use IvanBaric\Velora\Data\InvitationDispatchData;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;

final class ResendInvitationAction
{
    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, ?string $roleSlug = null): InvitationDispatchData
    {
        $plainToken = $invitation->prepareForResend($actorUserId, $roleSlug);
        $roleLabel = $this->resolveRoleLabel($invitation);
        $url = URL::temporarySignedRoute(
            'teams.invitation.accept',
            $invitation->expires_at ?? TeamInvitation::defaultExpiresAt(),
            ['token' => $plainToken],
        );

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation, $url, $roleLabel));

        return new InvitationDispatchData(
            invitation: $invitation->fresh() ?? $invitation,
            plainToken: $plainToken,
            url: $url,
            roleLabel: $roleLabel,
            message: 'Invitation resent to '.$invitation->email.'.',
        );
    }

    protected function resolveRoleLabel(TeamInvitation $invitation): string
    {
        return (string) (Role::query()
            ->availableToTeam($invitation->team_id)
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug);
    }
}
