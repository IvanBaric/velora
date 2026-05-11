<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
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

        if (! $membership->canRevoke()) {
            return ActionResult::error('Membership is already revoked.');
        }

        return DB::transaction(function () use ($membership, $email, $actorUserId): ActionResult {
            TeamInvitation::query()
                ->active()
                ->where('team_id', $membership->team_id)
                ->where('email', TeamInvitation::normalizeEmail($email))
                ->get()
                ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_left_team']));

            $result = $membership->revoke($actorUserId);

            return $result->success
                ? ActionResult::success('You left the team.')
                : $result;
        });
    }
}
