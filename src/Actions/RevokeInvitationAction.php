<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\ActionResult;

final class RevokeInvitationAction
{
    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, array $meta = []): ActionResult
    {
        if (! $invitation->canBeRevoked()) {
            return ActionResult::error(__('Prihvaćene pozivnice nije moguće opozvati.'));
        }

        DB::transaction(function () use ($invitation, $actorUserId, $meta): void {
            $invitation->markRevoked($actorUserId, $meta + ['reason' => 'manual_revoke']);
        });

        return ActionResult::success(__('Pozivnica je opozvana.'));
    }
}
