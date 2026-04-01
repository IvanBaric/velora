<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class LeaveTeamAction
{
    public function execute(TeamMembership $membership, string $email, ?int $actorUserId = null): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error('Team owner cannot leave the team this way.');
        }

        TeamInvitation::query()
            ->active()
            ->where('team_id', $membership->team_id)
            ->where('email', TeamInvitation::normalizeEmail($email))
            ->get()
            ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_left_team']));

        $membership->revoke($actorUserId);

        return ActionResult::success('You left the team.');
    }
}
