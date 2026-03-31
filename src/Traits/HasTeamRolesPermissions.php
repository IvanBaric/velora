<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use IvanBaric\Velora\Data\OperationResult;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\RoleAssigned;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Models\UserRole;

trait HasTeamRolesPermissions
{
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id', $this->roleOwnerKeyName());
    }

    public function roles(): BelongsToMany
    {
        $relation = $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id',
            $this->roleOwnerKeyName(),
            'id',
        )->withPivot(['team_id', 'assigned_by_user_id', 'assigned_at', 'expires_at'])
            ->withTimestamps();

        $teamId = $this->defaultRoleTeamId();
        if ($teamId !== null) {
            $relation->wherePivot('team_id', $teamId);
        }

        return $relation;
    }

    public function assignRole(int|string|Role $role, Team|int|null $team = null, ?int $assignedByUserId = null): OperationResult
    {
        $teamId = $this->resolveTeamId($team);
        $resolvedRole = $this->resolveRole($role, $teamId);
        if (! $resolvedRole) {
            return OperationResult::failure('Role not found for this team context.', code: 'role_not_found');
        }

        if (! $this->roleAssignable($resolvedRole)) {
            return OperationResult::failure('Role is not assignable.', code: 'role_not_assignable');
        }

        $userId = $this->resolveRoleOwnerId();
        $existing = UserRole::query()
            ->where('user_id', $userId)
            ->where('team_id', $teamId)
            ->where('role_id', $resolvedRole->getKey())
            ->first();

        if ($existing) {
            return OperationResult::success('Role is already assigned.', ['user_role_id' => $existing->getKey()], 'already_assigned');
        }

        $userRole = UserRole::query()->create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'role_id' => $resolvedRole->getKey(),
            'assigned_by_user_id' => $assignedByUserId ?? $this->resolveAssignerUserId(),
            'assigned_at' => now(),
        ]);

        event(new RoleAssigned($userRole, $resolvedRole));

        return OperationResult::success('Role assigned successfully.', ['user_role_id' => $userRole->getKey()]);
    }

    public function removeRole(int|string|Role $role, Team|int|null $team = null): OperationResult
    {
        $teamId = $this->resolveTeamId($team);
        $resolvedRole = $this->resolveRole($role, $teamId);
        if (! $resolvedRole) {
            return OperationResult::failure('Role not found for this team context.', code: 'role_not_found');
        }

        $deleted = UserRole::query()
            ->where('user_id', $this->resolveRoleOwnerId())
            ->where('team_id', $teamId)
            ->where('role_id', $resolvedRole->getKey())
            ->delete();

        if ($deleted === 0) {
            return OperationResult::success('Role was not assigned.', code: 'not_assigned');
        }

        return OperationResult::success('Role removed successfully.');
    }

    public function syncRoles(array|Collection|EloquentCollection $roles, Team|int|null $team = null, ?int $assignedByUserId = null): OperationResult
    {
        $teamId = $this->resolveTeamId($team);
        $resolvedRoles = $this->resolveRolesCollection($roles, $teamId);
        $roleIds = $resolvedRoles->pluck('id')->all();

        UserRole::query()
            ->where('user_id', $this->resolveRoleOwnerId())
            ->where('team_id', $teamId)
            ->whereNotIn('role_id', $roleIds)
            ->delete();

        foreach ($resolvedRoles as $roleModel) {
            UserRole::query()->updateOrCreate(
                [
                    'user_id' => $this->resolveRoleOwnerId(),
                    'team_id' => $teamId,
                    'role_id' => $roleModel->getKey(),
                ],
                [
                    'assigned_by_user_id' => $assignedByUserId ?? $this->resolveAssignerUserId(),
                    'assigned_at' => now(),
                    'expires_at' => null,
                ],
            );
        }

        return OperationResult::success('Roles synced successfully.');
    }

    public function hasRole(int|string|Role $role, Team|int|null $team = null): bool
    {
        $teamId = $this->resolveTeamId($team);
        $resolvedRole = $this->resolveRole($role, $teamId);
        if (! $resolvedRole) {
            return false;
        }

        return UserRole::query()
            ->active()
            ->where('user_id', $this->resolveRoleOwnerId())
            ->where('team_id', $teamId)
            ->where('role_id', $resolvedRole->getKey())
            ->exists();
    }

    public function hasPermission(string $permissionCode, Team|int|null $team = null): bool
    {
        $teamId = $this->resolveTeamId($team);

        if ($this->isTeamOwner($teamId)) {
            return true;
        }

        if ($this->hasSuperAdminRole($teamId)) {
            return true;
        }

        return UserRole::query()
            ->active()
            ->where('user_id', $this->resolveRoleOwnerId())
            ->where('team_id', $teamId)
            ->whereHas('role.permissionItems', fn ($query) => $query->where('code', $permissionCode)->where('is_active', true))
            ->exists();
    }

    public function hasPermissionTo(string $permissionCode, Team|int|null $team = null): bool
    {
        return $this->hasPermission($permissionCode, $team);
    }

    protected function hasSuperAdminRole(int $teamId): bool
    {
        $superadminSlug = (string) config('velora.roles.superadmin_slug', 'superadmin');

        return UserRole::query()
            ->active()
            ->where('user_id', $this->resolveRoleOwnerId())
            ->where('team_id', $teamId)
            ->whereHas('role', fn ($query) => $query->where('slug', $superadminSlug))
            ->exists();
    }

    protected function isTeamOwner(int $teamId): bool
    {
        if ($this instanceof TeamMembership) {
            return (int) $this->team_id === $teamId && (bool) $this->is_owner;
        }

        if (! method_exists($this, 'memberships')) {
            return false;
        }

        return $this->memberships()
            ->where('team_id', $teamId)
            ->where('is_owner', true)
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();
    }

    protected function resolveRolesCollection(array|Collection|EloquentCollection $roles, int $teamId): Collection
    {
        return collect($roles)
            ->map(fn (int|string|Role $role) => $this->resolveRole($role, $teamId))
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function resolveRole(int|string|Role $role, int $teamId): ?Role
    {
        if ($role instanceof Role) {
            return $this->roleInScope($role, $teamId) ? $role : null;
        }

        if (is_int($role) || ctype_digit((string) $role)) {
            $candidate = Role::query()->withoutGlobalScopes()->find((int) $role);

            return $candidate && $this->roleInScope($candidate, $teamId) ? $candidate : null;
        }

        $candidate = Role::query()
            ->withoutGlobalScopes()
            ->availableToTeam($teamId)
            ->where('slug', (string) $role)
            ->first();

        return $candidate ?: null;
    }

    protected function roleInScope(Role $role, int $teamId): bool
    {
        return $role->team_id === null || (int) $role->team_id === $teamId;
    }

    protected function roleAssignable(Role $role): bool
    {
        return $role->assignable && $role->is_active;
    }

    protected function resolveRoleOwnerId(): int
    {
        if ($this instanceof TeamMembership) {
            return (int) $this->user_id;
        }

        if ($this instanceof Model) {
            return (int) $this->getKey();
        }

        return (int) $this->id;
    }

    protected function resolveAssignerUserId(): ?int
    {
        return auth()->id() ? (int) auth()->id() : null;
    }

    protected function resolveTeamId(Team|int|null $team = null): int
    {
        if ($team instanceof Team) {
            return (int) $team->getKey();
        }

        if (is_int($team) || ctype_digit((string) $team)) {
            return (int) $team;
        }

        $teamId = $this->defaultRoleTeamId();

        return $teamId ?? (int) team()->getKey();
    }

    protected function defaultRoleTeamId(): ?int
    {
        if ($this instanceof TeamMembership) {
            // When eager-loading relations, Eloquent may invoke this on a "blank" model instance.
            // Avoid casting null to 0 which would incorrectly filter pivot rows to team_id=0.
            return isset($this->team_id) && $this->team_id ? (int) $this->team_id : null;
        }

        if (isset($this->team_id) && $this->team_id) {
            return (int) $this->team_id;
        }

        return null;
    }

    protected function roleOwnerKeyName(): string
    {
        if ($this instanceof TeamMembership) {
            return 'user_id';
        }

        return $this->getKeyName();
    }
}
