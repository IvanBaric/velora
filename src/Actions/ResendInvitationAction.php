<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Data\InvitationDispatchData;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;

final class ResendInvitationAction
{
    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, ?string $roleSlug = null): InvitationDispatchData
    {
        if (! $invitation->canBeResent()) {
            throw ValidationException::withMessages([
                'invitations' => __('Prihvaćene pozivnice nije moguće ponovno poslati.'),
            ]);
        }

        $resolvedRoleSlug = $roleSlug ?? $invitation->role_slug ?? TeamInvitation::defaultRoleSlug($invitation->team_id);
        $this->ensureRoleCanBeAssigned((string) $resolvedRoleSlug, (int) $invitation->team_id);

        [$invitation, $plainToken] = DB::transaction(function () use ($invitation, $actorUserId, $resolvedRoleSlug): array {
            $plainToken = $invitation->prepareForResend($actorUserId, $resolvedRoleSlug);

            return [$invitation->fresh() ?? $invitation, $plainToken];
        });

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
            message: __('Pozivnica je ponovno poslana na :email.', ['email' => $invitation->email]),
        );
    }

    protected function resolveRoleLabel(TeamInvitation $invitation): string
    {
        return (string) (Role::query()
            ->availableToTeam($invitation->team_id)
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug);
    }

    protected function ensureRoleCanBeAssigned(string $roleSlug, int $teamId): void
    {
        $exists = Role::query()
            ->availableToTeam($teamId)
            ->assignable()
            ->notHidden()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'invitations' => __('Uloga pozivnice više nije dostupna za dodjelu.'),
            ]);
        }
    }
}
