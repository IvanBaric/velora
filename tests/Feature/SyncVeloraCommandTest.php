<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Tests\TestCase;

final class SyncVeloraCommandTest extends TestCase
{
    public function test_velora_sync_creates_new_permission_and_role_records(): void
    {
        $this->configureBlueprint();

        Artisan::call('velora:sync');

        $permission = Permission::query()->where('slug', 'dashboard')->firstOrFail();
        $item = PermissionItem::query()->where('code', 'dashboard.view')->firstOrFail();
        $role = Role::query()->where('slug', 'admin')->firstOrFail();

        $this->assertSame('Dashboard', $permission->name);
        $this->assertSame($permission->getKey(), $item->permission_id);
        $this->assertTrue($role->permissionItems()->where('code', 'dashboard.view')->exists());
    }

    public function test_velora_safe_sync_does_not_overwrite_manual_runtime_changes(): void
    {
        $this->configureBlueprint();

        Artisan::call('velora:sync');

        $permission = Permission::query()->where('slug', 'dashboard')->firstOrFail();
        $item = PermissionItem::query()->where('code', 'dashboard.view')->firstOrFail();
        $role = Role::query()->where('slug', 'admin')->firstOrFail();

        $permission->forceFill(['label' => 'Manual group'])->save();
        $item->forceFill(['label' => 'Manual item'])->save();
        $role->forceFill(['label' => 'Manual role'])->save();
        $role->permissionItems()->sync([]);

        $this->configureBlueprint(
            groupLabel: 'Config group',
            itemLabel: 'Config item',
            adminLabel: 'Config role',
        );

        Artisan::call('velora:sync');

        $permission->refresh();
        $item->refresh();
        $role->refresh();

        $this->assertSame('Manual group', $permission->label);
        $this->assertSame('Manual item', $item->label);
        $this->assertSame('Manual role', $role->label);
        $this->assertSame(0, $role->permissionItems()->count());
    }

    public function test_velora_force_sync_overwrites_runtime_changes_from_config(): void
    {
        $this->configureBlueprint();

        Artisan::call('velora:sync');

        $permission = Permission::query()->where('slug', 'dashboard')->firstOrFail();
        $item = PermissionItem::query()->where('code', 'dashboard.view')->firstOrFail();
        $role = Role::query()->where('slug', 'admin')->firstOrFail();

        $permission->forceFill(['label' => 'Manual group'])->save();
        $item->forceFill(['label' => 'Manual item'])->save();
        $role->forceFill(['label' => 'Manual role'])->save();
        $role->permissionItems()->sync([]);

        $this->configureBlueprint(
            groupLabel: 'Config group',
            itemLabel: 'Config item',
            adminLabel: 'Config role',
        );

        Artisan::call('velora:sync', ['--force' => true]);

        $permission->refresh();
        $item->refresh();
        $role->refresh();

        $this->assertSame('Config group', $permission->label);
        $this->assertSame('Config item', $item->label);
        $this->assertSame('Config role', $role->label);
        $this->assertTrue($role->permissionItems()->where('code', 'dashboard.view')->exists());
    }

    public function test_permission_item_code_identifier_remains_stable_during_force_sync(): void
    {
        $this->configureBlueprint(itemSlug: 'view');

        Artisan::call('velora:sync');

        $item = PermissionItem::query()->where('code', 'dashboard.view')->firstOrFail();

        $this->configureBlueprint(itemSlug: 'view-renamed');

        Artisan::call('velora:sync', ['--force' => true]);

        $item->refresh();

        $this->assertSame('dashboard.view', $item->code);
        $this->assertSame('view-renamed', $item->slug);
        $this->assertSame(1, PermissionItem::query()->where('code', 'dashboard.view')->count());
    }

    public function test_superadmin_role_is_not_touched_without_explicit_override(): void
    {
        $this->configureBlueprint();

        Artisan::call('velora:sync');

        $superadmin = Role::query()->where('slug', 'superadmin')->firstOrFail();
        $superadmin->forceFill(['label' => 'Manual superadmin'])->save();
        $superadmin->permissionItems()->sync([]);

        $this->configureBlueprint(superadminLabel: 'Config superadmin');

        Artisan::call('velora:sync', ['--force' => true]);

        $superadmin->refresh();

        $this->assertSame('Manual superadmin', $superadmin->label);
        $this->assertSame(0, $superadmin->permissionItems()->count());
    }

    private function configureBlueprint(
        string $groupLabel = 'Dashboard',
        string $itemLabel = 'View dashboard',
        string $adminLabel = 'Administrator',
        string $superadminLabel = 'Superadministrator',
        string $itemSlug = 'view',
    ): void {
        config([
            'velora.sync.overwrite_existing' => false,
            'velora.sync.overwrite_superadmin' => false,
            'velora.roles.superadmin_slug' => 'superadmin',
            'velora.permissions' => [
                [
                    'name' => 'Dashboard',
                    'slug' => 'dashboard',
                    'label' => $groupLabel,
                    'description' => 'Dashboard access.',
                    'icon' => 'layout-grid',
                    'sort_order' => 10,
                    'items' => [
                        [
                            'name' => 'View',
                            'slug' => $itemSlug,
                            'code' => 'dashboard.view',
                            'label' => $itemLabel,
                            'description' => 'View dashboard.',
                            'sort_order' => 10,
                        ],
                    ],
                ],
            ],
            'velora.system_roles' => [
                [
                    'name' => 'Superadministrator',
                    'slug' => 'superadmin',
                    'label' => $superadminLabel,
                    'description' => 'Full system access.',
                    'redirect_to' => null,
                    'is_system' => true,
                    'is_locked' => true,
                    'assignable' => false,
                    'is_active' => true,
                    'sort_order' => 10,
                    'all_permissions' => true,
                ],
                [
                    'name' => 'Administrator',
                    'slug' => 'admin',
                    'label' => $adminLabel,
                    'description' => 'Administrative access.',
                    'redirect_to' => null,
                    'is_system' => true,
                    'is_locked' => true,
                    'assignable' => true,
                    'is_active' => true,
                    'sort_order' => 20,
                    'permissions' => [
                        'dashboard.view',
                    ],
                ],
            ],
        ]);
    }
}
