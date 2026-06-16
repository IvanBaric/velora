<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\TeamPermissions;

final class RevokeInvitationAction
{
    use AuthorizesActions;

    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, array $meta = []): ActionResult
    {
        if ($result = $this->authorizeVeloraAction(TeamPermissions::MANAGE_MEMBERS, $invitation)) {
            return $result;
        }

        if (! $invitation->canBeRevoked()) {
            return ActionResult::error(__('Prihvaćene pozivnice nije moguće opozvati.'));
        }

        DB::transaction(function () use ($invitation, $actorUserId, $meta): void {
            $invitation->markRevoked($actorUserId, $meta + ['reason' => 'manual_revoke']);
        });

        return ActionResult::success(__('Pozivnica je opozvana.'));
    }

    private function authorizeVeloraAction(string $ability, mixed $arguments = []): ?ActionResult
    {
        $result = $this->authorizeAction($ability, $arguments);

        return $result ? ActionResult::fromCorexis($result) : null;
    }
}
