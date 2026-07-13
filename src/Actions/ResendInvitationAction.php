<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Data\InvitationDispatchData;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\TeamPermissions;

final class ResendInvitationAction
{
    use AuthorizesActions;

    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, ?string $roleSlug = null): InvitationDispatchData
    {
        $this->authorizeActionOrFail(TeamPermissions::MANAGE_MEMBERS, $invitation);

        if ($invitation->grantsOwnerAccess() && ! $this->currentActorCanGrantOwnerAccess((int) $invitation->team_id)) {
            throw ValidationException::withMessages([
                'invitations' => __('Samo vlasnik organizacije može ponovno poslati pozivnicu s vlasničkim pristupom.'),
            ]);
        }

        [$invitation, $plainToken] = DB::transaction(function () use ($invitation, $actorUserId, $roleSlug): array {
            /** @var TeamInvitation $invitation */
            $invitation = TeamInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->canBeResent()) {
                throw ValidationException::withMessages([
                    'invitations' => __('Prihvaćene pozivnice nije moguće ponovno poslati.'),
                ]);
            }

            if ($invitation->grantsOwnerAccess() && ! $this->currentActorCanGrantOwnerAccess((int) $invitation->team_id)) {
                throw ValidationException::withMessages([
                    'invitations' => __('Samo vlasnik organizacije može ponovno poslati pozivnicu s vlasničkim pristupom.'),
                ]);
            }

            $resolvedRoleSlug = $roleSlug ?? $invitation->role_slug ?? TeamInvitation::defaultRoleSlug($invitation->team_id);
            $this->ensureRoleCanBeAssigned((string) $resolvedRoleSlug, (int) $invitation->team_id);
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

    private function currentActorCanGrantOwnerAccess(int $teamId): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $superadminAttribute = config('velora.access.superadmin_attribute');
        if (is_string($superadminAttribute) && $superadminAttribute !== '' && (bool) data_get($user, $superadminAttribute)) {
            return true;
        }

        return method_exists($user, 'ownsTeam') && $user->ownsTeam($teamId);
    }
}
