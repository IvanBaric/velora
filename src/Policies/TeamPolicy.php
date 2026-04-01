<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Policies;

use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\TeamPermissions;

class TeamPolicy
{
    public function update(mixed $user, Team $team): bool
    {
        if (! $user || ! method_exists($user, 'memberships')) {
            return false;
        }

        $membership = $user->memberships()
            ->withoutGlobalScopes()
            ->where('team_id', $team->getKey())
            ->first();

        return (bool) $membership?->is_owner;
    }

    public function manageMembers(mixed $user, Team $team): bool
    {
        if (! $user || ! method_exists($user, 'memberships')) {
            return false;
        }

        $membership = $user->memberships()
            ->withoutGlobalScopes()
            ->where('team_id', $team->getKey())
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->is_owner || $membership->hasPermissionTo(TeamPermissions::MANAGE_MEMBERS);
    }
}
