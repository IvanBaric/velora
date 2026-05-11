<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class RemoveTeamMemberAction
{
    public function execute(TeamMembership $membership, ?int $actorUserId = null): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error('Vlasnika tima nije moguće ukloniti.');
        }

        if (! $membership->canRevoke()) {
            return ActionResult::error('Članstvo je već opozvano.');
        }

        return DB::transaction(function () use ($membership, $actorUserId): ActionResult {
            $email = TeamInvitation::normalizeEmail((string) $membership->user?->email);

            TeamInvitation::query()
                ->active()
                ->where('team_id', $membership->team_id)
                ->where('email', $email)
                ->get()
                ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_removed']));

            $result = $membership->revoke($actorUserId);

            return $result->success
                ? ActionResult::success('Član je uklonjen iz tima.')
                : $result;
        });
    }
}
