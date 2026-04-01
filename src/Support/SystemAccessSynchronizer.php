<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;

class SystemAccessSynchronizer
{
    public function sync(): void
    {
        if (! $this->canSync()) {
            return;
        }

        $this->syncPermissionDictionary();
        $this->syncSystemRoles();
    }

    protected function canSync(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('permission_items')
            && Schema::hasTable('roles')
            && Schema::hasTable('role_permission_items');
    }

    protected function syncPermissionDictionary(): void
    {
        foreach ((array) config('velora.permissions', []) as $groupConfig) {
            /** @var Permission $permission */
            $permission = Permission::query()->updateOrCreate(
                ['slug' => (string) Arr::get($groupConfig, 'slug')],
                [
                    'name' => (string) Arr::get($groupConfig, 'name'),
                    'label' => Arr::get($groupConfig, 'label'),
                    'description' => Arr::get($groupConfig, 'description'),
                    'icon' => Arr::get($groupConfig, 'icon'),
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => (int) Arr::get($groupConfig, 'sort_order', 0),
                ],
            );

            foreach ((array) Arr::get($groupConfig, 'items', []) as $itemConfig) {
                PermissionItem::query()->updateOrCreate(
                    ['code' => (string) Arr::get($itemConfig, 'code')],
                    [
                        'permission_id' => $permission->getKey(),
                        'name' => (string) Arr::get($itemConfig, 'name'),
                        'slug' => (string) Arr::get($itemConfig, 'slug'),
                        'label' => Arr::get($itemConfig, 'label'),
                        'description' => Arr::get($itemConfig, 'description'),
                        'is_system' => true,
                        'is_active' => true,
                        'sort_order' => (int) Arr::get($itemConfig, 'sort_order', 0),
                    ],
                );
            }
        }
    }

    protected function syncSystemRoles(): void
    {
        foreach ((array) config('velora.system_roles', []) as $roleConfig) {
            /** @var Role $role */
            $role = Role::query()
                ->withoutGlobalScopes()
                ->updateOrCreate(
                    [
                        'team_id' => null,
                        'slug' => (string) Arr::get($roleConfig, 'slug'),
                    ],
                    [
                        'name' => (string) Arr::get($roleConfig, 'name'),
                        'label' => Arr::get($roleConfig, 'label'),
                        'description' => Arr::get($roleConfig, 'description'),
                        'redirect_to' => Arr::get($roleConfig, 'redirect_to'),
                        'is_system' => (bool) Arr::get($roleConfig, 'is_system', true),
                        'is_locked' => (bool) Arr::get($roleConfig, 'is_locked', true),
                        'assignable' => (bool) Arr::get($roleConfig, 'assignable', true),
                        'is_active' => (bool) Arr::get($roleConfig, 'is_active', true),
                        'sort_order' => (int) Arr::get($roleConfig, 'sort_order', 0),
                    ],
                );

            $permissionCodes = $this->resolveRolePermissionCodes($roleConfig);
            $permissionItemIds = PermissionItem::query()
                ->whereIn('code', $permissionCodes)
                ->pluck('id')
                ->all();

            $role->permissionItems()->sync($permissionItemIds);
        }
    }

    /**
     * @param  array<string, mixed>  $roleConfig
     * @return array<int, string>
     */
    protected function resolveRolePermissionCodes(array $roleConfig): array
    {
        if ((bool) Arr::get($roleConfig, 'all_permissions', false)) {
            return PermissionItem::query()->pluck('code')->all();
        }

        return array_values(array_unique(array_map(
            static fn (mixed $code): string => (string) $code,
            (array) Arr::get($roleConfig, 'permissions', []),
        )));
    }
}
