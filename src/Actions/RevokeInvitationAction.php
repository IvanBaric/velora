<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\ActionResult;

final class RevokeInvitationAction
{
    public function execute(TeamInvitation $invitation, ?int $actorUserId = null, array $meta = []): ActionResult
    {
        if (! $invitation->canBeRevoked()) {
            return ActionResult::error('Accepted invitations cannot be revoked.');
        }

        $invitation->markRevoked($actorUserId, $meta + ['reason' => 'manual_revoke']);

        return ActionResult::success('Invitation revoked.');
    }
}
