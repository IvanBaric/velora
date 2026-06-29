<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class LeaveTeamAction
{
    public function execute(TeamMembership $membership, string $email, ?int $actorUserId = null): ActionResult
    {
        return DB::transaction(function () use ($membership, $email, $actorUserId): ActionResult {
            /** @var TeamMembership $membership */
            $membership = TeamMembership::query()
                ->whereKey($membership->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->activeMemberCount((int) $membership->team_id) <= 1) {
                return ActionResult::error(__('Organizaciju nije moguće napustiti jer ste posljednji aktivni suradnik.'));
            }

            if ($membership->is_owner) {
                return ActionResult::error(__('Vlasnik organizacije ne može napustiti organizaciju na ovaj način.'));
            }

            if (! $membership->canRevoke()) {
                return ActionResult::error(__('Članstvo je već opozvano.'));
            }

            TeamInvitation::query()
                ->active()
                ->where('team_id', $membership->team_id)
                ->where('email', TeamInvitation::normalizeEmail($email))
                ->lockForUpdate()
                ->get()
                ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_left_team']));

            $result = $membership->revoke($actorUserId);

            return $result->success
                ? ActionResult::success(__('Napustili ste organizaciju.'))
                : $result;
        });
    }

    private function activeMemberCount(int $teamId): int
    {
        return TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', $teamId)
            ->where('status', TeamMembershipStatus::Active->value)
            ->lockForUpdate()
            ->count();
    }
}
