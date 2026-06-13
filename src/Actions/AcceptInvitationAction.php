<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Data\AcceptedInvitationData;
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
        $this->ensureInvitationRoleCanBeAssigned($invitation);

        [$acceptedInvitation, $membership] = DB::transaction(function () use ($user, $invitation): array {
            $membership = $this->attachInvitationMembership->execute($user, $invitation);

            $invitation->markAccepted((int) $user->getKey(), [
                'membership_id' => $membership->getKey(),
                'role_slug' => $invitation->role_slug,
            ]);

            return [
                $invitation->fresh(['team']) ?? $invitation,
                $membership->fresh() ?? $membership,
            ];
        });

        /** @var TeamInvitation $acceptedInvitation */
        /** @var TeamMembership $membership */
        event(new InvitationAccepted($acceptedInvitation, $membership, $user));

        return new AcceptedInvitationData(
            user: $user,
            invitation: $acceptedInvitation,
            membership: $membership,
            message: __('Pridružili ste se timu :team.', ['team' => $acceptedInvitation->team->name]),
        );
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
