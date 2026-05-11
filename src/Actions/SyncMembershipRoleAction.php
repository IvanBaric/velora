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
            return ActionResult::error('Ulogu vlasnika nije moguće promijeniti.');
        }

        $roleExists = Role::query()
            ->availableToTeam($membership->team_id)
            ->assignable()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $roleExists) {
            return ActionResult::error('Uloga nije pronađena za ovaj tim.');
        }

        $result = $membership->syncRoles([$roleSlug]);

        if (! $result->ok) {
            return ActionResult::fromOperationResult($result);
        }

        return ActionResult::success('Uloga člana je ažurirana.');
    }
}
