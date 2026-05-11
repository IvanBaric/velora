<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Data\AcceptedInvitationData;
use IvanBaric\Velora\Events\InvitationAccepted;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class AcceptInvitationAction
{
    public function __construct(
        private readonly AttachInvitationMembershipAction $attachInvitationMembership,
    ) {}

    public function execute(Model $user, TeamInvitation $invitation): AcceptedInvitationData
    {
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
            message: 'You joined team '.$acceptedInvitation->team->name.'.',
        );
    }
}
