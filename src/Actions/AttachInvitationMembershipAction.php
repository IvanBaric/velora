<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class AttachInvitationMembershipAction
{
    public function execute(Model $user, TeamInvitation $invitation): TeamMembership
    {
        /** @var TeamMembership $membership */
        $membership = TeamMembership::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'team_id' => $invitation->team_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => TeamMembershipStatus::Active,
                    'is_owner' => false,
                    'invited_by_user_id' => $invitation->invited_by_user_id,
                    'invited_email' => $invitation->email,
                    'joined_at' => now(),
                ],
            );

        if (! $membership->isActive()) {
            $membership->activate((int) $user->getKey());
        }

        if ($invitation->role_slug) {
            $membership->syncRoles([$invitation->role_slug], $invitation->team_id, $invitation->invited_by_user_id);
        }

        return $membership;
    }
}
