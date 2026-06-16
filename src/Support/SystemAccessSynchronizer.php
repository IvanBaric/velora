<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Velora\Data\OperationResult;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;

class SystemAccessSynchronizer
{
    public function sync(?bool $overwriteExisting = null): OperationResult
    {
        $result = $this->emptyResult();

        if (! $this->canSync()) {
            return OperationResult::failure(
                'Velora tables are missing. Run migrations before syncing.',
                $result,
                'missing_tables',
            );
        }

        $overwriteExisting ??= (bool) config('velora.sync.overwrite_existing', false);
        $overwriteSuperadmin = (bool) config('velora.sync.overwrite_superadmin', false);

        DB::transaction(function () use ($overwriteExisting, $overwriteSuperadmin, &$result): void {
            $this->syncPermissionDictionary($overwriteExisting, $result);
            $this->syncSystemRoles($overwriteExisting, $overwriteSuperadmin, $result);
        });

        return OperationResult::success(
            'Velora permissions and system roles synced.',
            $result,
            'synced',
        );
    }

    public function canSync(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('permission_items')
            && Schema::hasTable('roles')
            && Schema::hasTable('role_permission_items');
    }

    /**
     * @param  array{created: int, updated: int, skipped: int}  $result
     */
    protected function syncPermissionDictionary(bool $overwriteExisting, array &$result): void
    {
        $configuredGroupSlugs = [];
        $configuredItemCodes = [];

        foreach ($this->permissionGroups() as $groupConfig) {
            $groupSlug = (string) Arr::get($groupConfig, 'slug');

            if ($groupSlug === '') {
                continue;
            }

            $configuredGroupSlugs[] = $groupSlug;
            $groupPayload = [
                'name' => (string) Arr::get($groupConfig, 'name'),
                'label' => Arr::get($groupConfig, 'label'),
                'description' => Arr::get($groupConfig, 'description'),
                'icon' => Arr::get($groupConfig, 'icon'),
                'is_system' => true,
                'is_active' => true,
                'sort_order' => (int) Arr::get($groupConfig, 'sort_order', 0),
            ];

            /** @var Permission|null $permission */
            $permission = Permission::query()
                ->where('slug', $groupSlug)
                ->first();

            if (! $permission instanceof Permission) {
                /** @var Permission $permission */
                $permission = Permission::query()->create([
                    'slug' => $groupSlug,
                    ...$groupPayload,
                ]);
                $result['created']++;
            } elseif ($overwriteExisting) {
                $this->updateModel($permission, $groupPayload, $result);
            } else {
                $result['skipped']++;
            }

            foreach ((array) Arr::get($groupConfig, 'items', []) as $itemConfig) {
                $itemCode = (string) Arr::get($itemConfig, 'code');

                if ($itemCode === '') {
                    continue;
                }

                $configuredItemCodes[] = $itemCode;
                $itemPayload = [
                    'permission_id' => $permission->getKey(),
                    'name' => (string) Arr::get($itemConfig, 'name'),
                    'slug' => (string) Arr::get($itemConfig, 'slug'),
                    'label' => Arr::get($itemConfig, 'label'),
                    'description' => Arr::get($itemConfig, 'description'),
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => (int) Arr::get($itemConfig, 'sort_order', 0),
                ];

                /** @var PermissionItem|null $item */
                $item = PermissionItem::query()
                    ->where('code', $itemCode)
                    ->first();

                if (! $item instanceof PermissionItem) {
                    PermissionItem::query()->create([
                        'code' => $itemCode,
                        ...$itemPayload,
                    ]);
                    $result['created']++;

                    continue;
                }

                if (! $overwriteExisting) {
                    $result['skipped']++;

                    continue;
                }

                $this->updateModel($item, $itemPayload, $result);
            }
        }

        if ($overwriteExisting) {
            $result['updated'] += Permission::query()
                ->where('is_system', true)
                ->whereNotIn('slug', $configuredGroupSlugs)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $result['updated'] += PermissionItem::query()
                ->where('is_system', true)
                ->whereNotIn('code', $configuredItemCodes)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return;
        }

        $result['skipped'] += Permission::query()
            ->where('is_system', true)
            ->whereNotIn('slug', $configuredGroupSlugs)
            ->where('is_active', true)
            ->count();

        $result['skipped'] += PermissionItem::query()
            ->where('is_system', true)
            ->whereNotIn('code', $configuredItemCodes)
            ->where('is_active', true)
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function permissionGroups(): array
    {
        $groups = (array) config('velora.permissions', []);

        foreach ((array) config('velora.permission_sources', []) as $source) {
            if (! is_string($source) || $source === '') {
                continue;
            }

            $sourceGroups = config($source.'.velora_permissions', config($source.'.permissions', []));

            if (! is_array($sourceGroups) || ! array_is_list($sourceGroups)) {
                continue;
            }

            foreach ($sourceGroups as $sourceGroup) {
                if (is_array($sourceGroup) && isset($sourceGroup['items'])) {
                    $groups[] = $sourceGroup;
                }
            }
        }

        return $groups;
    }

    /**
     * @param  array{created: int, updated: int, skipped: int}  $result
     */
    protected function syncSystemRoles(bool $overwriteExisting, bool $overwriteSuperadmin, array &$result): void
    {
        $superadminSlug = (string) config('velora.roles.superadmin_slug', 'superadmin');

        foreach ((array) config('velora.system_roles', []) as $roleConfig) {
            $roleSlug = (string) Arr::get($roleConfig, 'slug');

            if ($roleSlug === '') {
                continue;
            }

            $rolePayload = [
                'name' => (string) Arr::get($roleConfig, 'name'),
                'label' => Arr::get($roleConfig, 'label'),
                'description' => Arr::get($roleConfig, 'description'),
                'redirect_to' => Arr::get($roleConfig, 'redirect_to'),
                'is_system' => (bool) Arr::get($roleConfig, 'is_system', true),
                'is_locked' => (bool) Arr::get($roleConfig, 'is_locked', true),
                'assignable' => (bool) Arr::get($roleConfig, 'assignable', true),
                'is_active' => (bool) Arr::get($roleConfig, 'is_active', true),
                'sort_order' => (int) Arr::get($roleConfig, 'sort_order', 0),
            ];

            /** @var Role|null $role */
            $role = Role::query()
                ->withoutGlobalScopes()
                ->whereNull('team_id')
                ->where('slug', $roleSlug)
                ->first();

            if (! $role instanceof Role) {
                /** @var Role $role */
                $role = Role::query()
                    ->withoutGlobalScopes()
                    ->create([
                        'team_id' => null,
                        'slug' => $roleSlug,
                        ...$rolePayload,
                    ]);

                $role->permissionItems()->sync($this->permissionItemIdsForRole($roleConfig));
                $result['created']++;

                continue;
            }

            if ($roleSlug === $superadminSlug && ! $overwriteSuperadmin) {
                $result['skipped']++;

                continue;
            }

            if (! $overwriteExisting) {
                $result['skipped']++;

                continue;
            }

            $wasUpdated = $this->updateModel($role, $rolePayload, $result, countSkipped: false);
            $changes = $role->permissionItems()->sync($this->permissionItemIdsForRole($roleConfig));
            $permissionsChanged = $changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== [];

            if ($permissionsChanged && ! $wasUpdated) {
                $result['updated']++;

                continue;
            }

            if (! $permissionsChanged && ! $wasUpdated) {
                $result['skipped']++;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $roleConfig
     * @return array<int, int>
     */
    protected function permissionItemIdsForRole(array $roleConfig): array
    {
        $permissionCodes = $this->resolveRolePermissionCodes($roleConfig);

        return PermissionItem::query()
            ->whereIn('code', $permissionCodes)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $roleConfig
     * @return array<int, string>
     */
    protected function resolveRolePermissionCodes(array $roleConfig): array
    {
        if ((bool) Arr::get($roleConfig, 'all_permissions', false)) {
            return PermissionItem::query()
                ->where('is_active', true)
                ->pluck('code')
                ->all();
        }

        return array_values(array_unique(array_map(
            static fn (mixed $code): string => (string) $code,
            (array) Arr::get($roleConfig, 'permissions', []),
        )));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{created: int, updated: int, skipped: int}  $result
     */
    protected function updateModel(Model $model, array $payload, array &$result, bool $countSkipped = true): bool
    {
        $model->forceFill($payload);

        if (! $model->isDirty()) {
            if ($countSkipped) {
                $result['skipped']++;
            }

            return false;
        }

        $model->save();
        $result['updated']++;

        return true;
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    private function emptyResult(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
    }
}
