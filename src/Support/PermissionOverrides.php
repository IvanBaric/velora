<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

final class PermissionOverrides
{
    /**
     * @return bool|null Null means no explicit override.
     */
    public function forOwner(string $permissionCode): ?bool
    {
        return $this->resolve((array) config('velora.authorization.overrides.owner', []), $permissionCode);
    }

    /**
     * @param  iterable<int, string>  $roleSlugs
     * @return bool|null Null means no explicit override.
     */
    public function forRoles(iterable $roleSlugs, string $permissionCode): ?bool
    {
        foreach ($roleSlugs as $roleSlug) {
            $override = $this->forRole($roleSlug, $permissionCode);

            if ($override !== null) {
                return $override;
            }
        }

        return null;
    }

    /**
     * @return bool|null Null means no explicit override.
     */
    public function forRole(string $roleSlug, string $permissionCode): ?bool
    {
        return $this->resolve((array) config('velora.authorization.overrides.roles.'.$roleSlug, []), $permissionCode);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resolve(array $rules, string $permissionCode): ?bool
    {
        foreach ($this->keysFor($permissionCode) as $key) {
            if (array_key_exists($key, $rules)) {
                return (bool) $rules[$key];
            }
        }

        if (array_key_exists('*', $rules)) {
            return (bool) $rules['*'];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function keysFor(string $permissionCode): array
    {
        return array_values(array_unique([
            $permissionCode,
            ...match ($permissionCode) {
                TeamPermissions::TEAMS_CREATE => ['can_create_team'],
                TeamPermissions::TEAMS_UPDATE => ['can_update_team', 'can_change_team_name'],
                TeamPermissions::TEAMS_DELETE => ['can_delete_team'],
                TeamPermissions::MANAGE_MEMBERS => ['can_manage_members'],
                TeamPermissions::MANAGE_ROLES => ['can_manage_roles', 'can_add_roles'],
                default => [],
            },
        ]));
    }
}
