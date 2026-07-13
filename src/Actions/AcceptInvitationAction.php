<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Data\AcceptedInvitationData;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Events\InvitationAccepted;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class AcceptInvitationAction
{
    public function __construct(
        private readonly AttachInvitationMembershipAction $attachInvitationMembership,
    ) {}

    public function execute(Model $user, TeamInvitation $invitation): AcceptedInvitationData
    {
        return $this->executeWithUserResolver(
            $invitation,
            static fn (TeamInvitation $lockedInvitation): Model => $user,
        );
    }

    /**
     * @param  Closure(TeamInvitation): Model  $resolveUser
     */
    public function executeWithUserResolver(TeamInvitation $invitation, Closure $resolveUser): AcceptedInvitationData
    {
        [$acceptedInvitation, $membership, $user] = DB::transaction(function () use ($resolveUser, $invitation): array {
            /** @var TeamInvitation $invitation */
            $invitation = TeamInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureInvitationCanStillBeAccepted($invitation);
            $this->lockInvitationTeam($invitation);
            $this->ensureInvitationRoleCanBeAssigned($invitation);

            $user = $resolveUser($invitation);
            $this->ensureUserMatchesInvitation($user, $invitation);

            $membership = $this->attachInvitationMembership->execute($user, $invitation);

            $invitation->markAccepted((int) $user->getKey(), [
                'membership_id' => $membership->getKey(),
                'role_slug' => $invitation->role_slug,
            ]);

            return [
                $invitation->fresh(['team']) ?? $invitation,
                $membership->fresh() ?? $membership,
                $user,
            ];
        });

        /** @var TeamInvitation $acceptedInvitation */
        /** @var TeamMembership $membership */
        event(new InvitationAccepted($acceptedInvitation, $membership, $user));

        return new AcceptedInvitationData(
            user: $user,
            invitation: $acceptedInvitation,
            membership: $membership,
            message: __('Pridružili ste se organizaciji :team.', ['team' => $acceptedInvitation->team->name]),
        );
    }

    protected function lockInvitationTeam(TeamInvitation $invitation): void
    {
        $teamClass = velora_team_model();
        $team = $teamClass::query()
            ->whereKey($invitation->team_id)
            ->lockForUpdate()
            ->firstOrFail();

        $invitation->setRelation('team', $team);
    }

    protected function ensureUserMatchesInvitation(Model $user, TeamInvitation $invitation): void
    {
        $userEmail = TeamInvitation::normalizeEmail((string) $user->getAttribute('email'));

        if ($userEmail === $invitation->email) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('Pozivnica nije namijenjena ovom korisničkom računu.'),
        ]);
    }

    protected function ensureInvitationCanStillBeAccepted(TeamInvitation $invitation): void
    {
        if ($invitation->canBeAccepted()) {
            return;
        }

        if ($invitation->isExpired()) {
            $invitation->markExpired();

            throw ValidationException::withMessages([
                'email' => __('Ova pozivnica je istekla.'),
            ]);
        }

        $message = match ($invitation->status) {
            TeamInvitationStatus::Accepted => __('Ova pozivnica je već iskorištena.'),
            TeamInvitationStatus::Revoked => __('Ova pozivnica je opozvana.'),
            default => __('Ova pozivnica više nije dostupna.'),
        };

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    protected function ensureInvitationRoleCanBeAssigned(TeamInvitation $invitation): void
    {
        $roleSlug = (string) ($invitation->role_slug ?? '');

        $exists = $roleSlug !== ''
            && Role::query()
                ->availableToTeam($invitation->team_id)
                ->assignable()
                ->notHidden()
                ->where('slug', $roleSlug)
                ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'email' => __('Uloga pozivnice više nije dostupna za dodjelu.'),
            ]);
        }
    }
}
