<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Models\Team;

class PermissionRegistrar
{
    public function userCan(mixed $user, string $ability, array $arguments = []): ?bool
    {
        if (! $user || ! method_exists($user, 'hasPermission')) {
            return null;
        }

        if (! str_contains($ability, '.')) {
            return null;
        }

        return $user->hasPermission($ability, $this->resolveTeam($arguments));
    }

    public function userHasRole(mixed $user, string $role, array $arguments = []): bool
    {
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($role, $this->resolveTeam($arguments));
    }

    protected function resolveTeam(array $arguments = []): Team|int|null
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof Team) {
                return $argument;
            }

            if ($argument instanceof Model) {
                if (isset($argument->team_id) && $argument->team_id) {
                    return (int) $argument->team_id;
                }
            }
        }

        return function_exists('team') ? team() : null;
    }
}
