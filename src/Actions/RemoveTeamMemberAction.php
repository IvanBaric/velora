<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\TeamPermissions;

final class RemoveTeamMemberAction
{
    use AuthorizesActions;

    public function execute(TeamMembership $membership, ?int $actorUserId = null): ActionResult
    {
        if ($result = $this->authorizeVeloraAction(TeamPermissions::MANAGE_MEMBERS, $membership)) {
            return $result;
        }

        if (! $this->currentActorOwnsTeam((int) $membership->team_id)) {
            return ActionResult::error(__('Samo vlasnik organizacije može ukloniti suradnike.'));
        }

        if ($membership->isOwner()) {
            return ActionResult::error(__('Vlasnika organizacije nije moguće ukloniti.'));
        }

        if (! $membership->canRevoke()) {
            return ActionResult::error(__('Članstvo je već opozvano.'));
        }

        return DB::transaction(function () use ($membership, $actorUserId): ActionResult {
            /** @var TeamMembership $membership */
            $membership = TeamMembership::query()
                ->whereKey($membership->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $email = TeamInvitation::normalizeEmail((string) $membership->user?->email);

            TeamInvitation::query()
                ->active()
                ->where('team_id', $membership->team_id)
                ->where('email', $email)
                ->lockForUpdate()
                ->get()
                ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked($actorUserId, ['reason' => 'member_removed']));

            $result = $membership->revoke($actorUserId);

            return $result->success
                ? ActionResult::success(__('Suradnik je uklonjen iz organizacije.'))
                : $result;
        });
    }

    private function authorizeVeloraAction(string $ability, mixed $arguments = []): ?ActionResult
    {
        $result = $this->authorizeAction($ability, $arguments);

        return $result ? ActionResult::fromCorexis($result) : null;
    }

    private function currentActorOwnsTeam(int $teamId): bool
    {
        $user = auth()->user();

        return $user && method_exists($user, 'ownsTeam') && $user->ownsTeam($teamId);
    }
}
