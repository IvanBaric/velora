<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;

final class SyncMembershipRoleAction
{
    public function execute(TeamMembership $membership, string $roleSlug): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error('Owner role cannot be changed.');
        }

        $roleExists = Role::query()
            ->availableToTeam($membership->team_id)
            ->assignable()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $roleExists) {
            return ActionResult::error('Role not found for this team context.');
        }

        $result = $membership->syncRoles([$roleSlug]);

        if (! $result->ok) {
            return ActionResult::fromOperationResult($result);
        }

        return ActionResult::success('Member role updated.');
    }
}
