<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Exceptions\UnableToResolveCurrentTeam;

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

        if ($ability === 'teams.create' && $arguments === []) {
            return $this->userCanCreateTeam($user);
        }

        try {
            return $user->hasPermission($ability, $this->resolveTeam($arguments));
        } catch (UnableToResolveCurrentTeam) {
            return null;
        }
    }

    public function userHasRole(mixed $user, string $role, array $arguments = []): bool
    {
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return $user->hasRole($role, $this->resolveTeam($arguments));
        } catch (UnableToResolveCurrentTeam) {
            return false;
        }
    }

    protected function resolveTeam(array $arguments = []): Model|int|null
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof Model && velora_is_team_model($argument)) {
                return $argument;
            }

            if ($argument instanceof Model) {
                if (isset($argument->team_id) && $argument->team_id) {
                    return (int) $argument->team_id;
                }
            }

            if (is_int($argument) || (is_string($argument) && ctype_digit($argument))) {
                return (int) $argument;
            }
        }

        return function_exists('team') ? team() : null;
    }

    protected function userCanCreateTeam(mixed $user): ?bool
    {
        $attribute = config('velora.access.superadmin_attribute');

        if (is_string($attribute) && $attribute !== '' && (bool) data_get($user, $attribute)) {
            return true;
        }

        return null;
    }
}
