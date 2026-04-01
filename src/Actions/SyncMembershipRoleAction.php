<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class SyncMembershipRoleAction
{
    public function execute(TeamMembership $membership, string $roleSlug): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error('Owner role cannot be changed.');
        }

        $membership->syncRoles([$roleSlug]);

        return ActionResult::success('Member role updated.');
    }
}
