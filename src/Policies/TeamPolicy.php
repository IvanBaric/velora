<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Policies;

use App\Models\User;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\TeamPermissions;

class TeamPolicy
{
    public function update(User $user, Team $team): bool
    {
        $membership = $user->membershipForCurrentTeam();

        return (bool) $membership?->is_owner;
    }

    public function manageMembers(User $user, Team $team): bool
    {
        $membership = $user->membershipForCurrentTeam();
        if (! $membership) {
            return false;
        }

        return $membership->is_owner || $membership->hasPermissionTo(TeamPermissions::MANAGE_MEMBERS);
    }
}
