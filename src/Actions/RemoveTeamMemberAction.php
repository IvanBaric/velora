<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class RemoveTeamMemberAction
{
    public function execute(TeamMembership $membership, ?int $actorUserId = null): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error('Team owner cannot be removed.');
        }

        $email = TeamInvitation::normalizeEmail((string) $membership->user?->email);

        TeamInvitation::query()
            ->active()
            ->where('team_id', $membership->team_id)
            ->where('email', $email)
            ->get()
            ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_removed']));

        $membership->revoke($actorUserId);

        return ActionResult::success('Member removed from team.');
    }
}
