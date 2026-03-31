<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use IvanBaric\Velora\Models\Role;

final class TeamRoles
{
    public static function manager(int|string|null $teamId = null): ?Role
    {
        return Role::query()
            ->availableToTeam($teamId)
            ->whereHas('permissionItems', fn ($query) => $query->where('code', TeamPermissions::MANAGE_MEMBERS))
            ->first();
    }
}
