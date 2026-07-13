<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Policies;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Support\TeamPermissions;

class TeamPolicy
{
    public function update(mixed $user, Model $team): bool
    {
        if (! $user || ! method_exists($user, 'memberships')) {
            return false;
        }

        return (bool) $user->hasPermission(TeamPermissions::TEAMS_UPDATE, $team);
    }

    public function manageMembers(mixed $user, Model $team): bool
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

        return $membership->hasPermissionTo(TeamPermissions::MANAGE_MEMBERS);
    }
}
