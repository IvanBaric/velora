<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\UserRole;

final class GrantablePermissions
{
    /**
     * @return array<int, int>
     */
    public function idsFor(?Model $user, int $teamId): array
    {
        if (! $user || $teamId <= 0) {
            return [];
        }

        if ($this->isGlobalSuperadmin($user)) {
            return PermissionItem::query()
                ->where('is_active', true)
                ->whereHas('permission', fn ($query) => $query->where('is_active', true))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return UserRole::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->where('team_id', $teamId)
            ->whereHas('role', fn ($query) => $query->withoutGlobalScopes()->where('is_active', true))
            ->with(['role.permissionItems' => fn ($query) => $query
                ->where('permission_items.is_active', true)
                ->whereHas('permission', fn ($permissionQuery) => $permissionQuery->where('is_active', true))])
            ->get()
            ->flatMap(fn (UserRole $assignment) => $assignment->role?->permissionItems?->pluck('id') ?? collect())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canGrantAll(?Model $user, int $teamId, array $permissionItemIds): bool
    {
        if ($permissionItemIds === []) {
            return true;
        }

        $allowed = array_flip($this->idsFor($user, $teamId));

        foreach ($permissionItemIds as $permissionItemId) {
            if (! isset($allowed[(int) $permissionItemId])) {
                return false;
            }
        }

        return true;
    }

    private function isGlobalSuperadmin(Model $user): bool
    {
        return isset($user->is_superadmin) && (bool) $user->is_superadmin;
    }
}
