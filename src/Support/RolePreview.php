<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use IvanBaric\Velora\Models\Role;

class RolePreview
{
    private const SESSION_KEY = 'velora.role_preview';

    public function start(Role $role, int $teamId): void
    {
        session()->put(self::SESSION_KEY, [
            'team_id' => $teamId,
            'role_id' => (int) $role->getKey(),
            'role_uuid' => (string) $role->uuid,
            'role_name' => (string) ($role->label ?: $role->name),
            'role_slug' => (string) $role->slug,
            'started_at' => now()->toIso8601String(),
        ]);
    }

    public function stop(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function activeForTeam(int $teamId): ?Role
    {
        $state = $this->state();
        if (! $state) {
            return null;
        }

        if ((int) ($state['team_id'] ?? 0) !== $teamId) {
            return null;
        }

        $query = Role::query()
            ->withoutGlobalScopes()
            ->availableToTeam($teamId);

        $roleId = (int) ($state['role_id'] ?? 0);
        if ($roleId > 0) {
            return (clone $query)->whereKey($roleId)->first();
        }

        $roleSlug = (string) ($state['role_slug'] ?? '');
        if ($roleSlug === '') {
            return null;
        }

        return $query->where('slug', $roleSlug)->first();
    }

    public function allows(string $permissionCode, int $teamId): ?bool
    {
        $role = $this->activeForTeam($teamId);
        if (! $role) {
            return null;
        }

        return $role->permissionItems()
            ->where('code', $permissionCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return array{team_id?: int, role_id?: int, role_uuid?: string, role_name?: string, role_slug?: string, started_at?: string}|null
     */
    public function state(): ?array
    {
        if (! app()->bound('session')) {
            return null;
        }

        $state = session()->get(self::SESSION_KEY);

        return is_array($state) ? $state : null;
    }

    public function isActive(): bool
    {
        return $this->state() !== null;
    }
}
