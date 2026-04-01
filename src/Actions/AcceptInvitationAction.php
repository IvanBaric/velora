<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Data\AcceptedInvitationData;
use IvanBaric\Velora\Events\InvitationAccepted;
use IvanBaric\Velora\Models\TeamInvitation;

final class AcceptInvitationAction
{
    public function __construct(
        private readonly AttachInvitationMembershipAction $attachInvitationMembership,
    ) {}

    public function execute(Model $user, TeamInvitation $invitation): AcceptedInvitationData
    {
        $membership = $this->attachInvitationMembership->execute($user, $invitation);

        $invitation->markAccepted((int) $user->getKey(), [
            'membership_id' => $membership->getKey(),
            'role_slug' => $invitation->role_slug,
        ]);

        event(new InvitationAccepted($invitation, $membership, $user));

        return new AcceptedInvitationData(
            user: $user,
            invitation: $invitation->fresh(['team']) ?? $invitation,
            membership: $membership->fresh() ?? $membership,
            message: 'You joined team '.$invitation->team->name.'.',
        );
    }
}
