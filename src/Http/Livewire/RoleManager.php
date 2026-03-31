<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;
use Livewire\Attributes\On;
use Livewire\Component;

class RoleManager extends Component
{
    public bool $isOpen = false;

    public bool $isFormOpen = false;

    public bool $isDeleteConfirmOpen = false;

    public ?string $roleUuid = null;

    public string $name = '';

    public string $slug = '';

    public array $selectedPermissionItems = [];

    public ?string $replacementRoleUuid = null;

    public int $pendingDeleteUserCount = 0;

    public string $permissionSearch = '';

    #[On('open-role-manager')]
    public function open(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function createRole(): void
    {
        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function editRole(string $roleUuid): void
    {
        $role = $this->resolveRoleByUuid($roleUuid);

        abort_if($role->isGlobal(), 403);

        $this->roleUuid = (string) $role->uuid;
        $this->name = (string) $role->name;
        $this->slug = (string) $role->slug;
        $this->selectedPermissionItems = $role->permissionItems()->pluck('permission_items.uuid')->map(fn ($uuid) => (string) $uuid)->all();
        $this->isFormOpen = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'selectedPermissionItems' => ['array'],
        ]);

        $teamId = (int) team()->getKey();

        $payload = [
            'team_id' => $teamId,
            'name' => $data['name'],
            'slug' => $this->roleUuid
                ? (string) $this->resolveRoleByUuid($this->roleUuid)->slug
                : $this->generateUniqueSlug((string) $data['name'], $teamId),
            'label' => $data['name'],
            'is_system' => false,
            'is_locked' => false,
            'assignable' => true,
            'is_active' => true,
        ];

        if ($this->roleUuid) {
            $role = $this->resolveRoleByUuid($this->roleUuid);
            abort_if($role->isGlobal() || $role->is_locked, 403);
            $role->update($payload);
        } else {
            $role = Role::query()->create($payload);
        }

        $role->permissionItems()->sync($this->resolvePermissionItemIds($this->selectedPermissionItems));

        $this->resetForm();
        $this->isFormOpen = false;
        $this->dispatch('notify', 'Role saved.');
    }

    public function clearPermissions(): void
    {
        $this->selectedPermissionItems = [];
    }

    public function selectAllPermissions(): void
    {
        $this->selectedPermissionItems = $this->allPermissionItemIds();
    }

    public function selectAllFilteredPermissions(): void
    {
        $this->selectedPermissionItems = $this->filteredPermissionItemIds();
    }

    public function confirmDelete(string $roleUuid): void
    {
        $role = $this->resolveRoleByUuid($roleUuid);
        abort_if($role->isGlobal() || $role->is_locked, 403);

        $this->roleUuid = (string) $role->uuid;
        $this->replacementRoleUuid = null;
        $this->pendingDeleteUserCount = (int) $role->userRoles()->count();
        $this->isDeleteConfirmOpen = true;
    }

    public function deleteRole(): void
    {
        $role = $this->resolveRoleByUuid((string) $this->roleUuid);
        abort_if($role->isGlobal() || $role->is_locked, 403);

        $userCount = $role->userRoles()->count();
        if ($userCount > 0 && ! $this->replacementRoleUuid) {
            $this->addError('replacementRoleUuid', 'Select a replacement role.');

            return;
        }

        if ($userCount > 0) {
            $replacementRoleId = $this->resolveRoleByUuid((string) $this->replacementRoleUuid)->getKey();
            $role->userRoles()->update(['role_id' => $replacementRoleId]);
        }

        $role->permissionItems()->detach();
        $role->delete();

        $this->isDeleteConfirmOpen = false;
        $this->pendingDeleteUserCount = 0;
        $this->resetForm();
        $this->dispatch('notify', 'Role deleted.');
    }

    public function getRolesProperty(): Collection
    {
        return Role::query()
            ->availableToTeam(team()->getKey())
            ->notHidden()
            ->withCount('permissionItems')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsProperty(): Collection
    {
        return Permission::query()
            ->where('is_active', true)
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    public function getFilteredPermissionsProperty(): Collection
    {
        $search = trim(mb_strtolower($this->permissionSearch));
        if ($search === '') {
            return $this->permissions;
        }

        return $this->permissions
            ->map(function (Permission $group) use ($search): Permission {
                $groupName = mb_strtolower((string) $group->name);
                $groupLabel = mb_strtolower((string) $group->label);

                $items = $group->items
                    ->filter(function ($item) use ($search): bool {
                        $name = mb_strtolower((string) ($item->name ?? ''));
                        $label = mb_strtolower((string) ($item->label ?? ''));
                        $code = mb_strtolower((string) ($item->code ?? ''));

                        return str_contains($name, $search)
                            || str_contains($label, $search)
                            || str_contains($code, $search);
                    })
                    ->values();

                // Match whole group name/label to include all its items.
                if (str_contains($groupName, $search) || str_contains($groupLabel, $search)) {
                    $items = $group->items->values();
                }

                $clone = clone $group;
                $clone->setRelation('items', $items);

                return $clone;
            })
            ->filter(fn (Permission $group) => $group->items->isNotEmpty())
            ->values();
    }

    public function render(): View
    {
        return view('velora::livewire.role-manager', [
            'roles' => $this->roles,
            'permissions' => $this->filteredPermissions,
        ]);
    }

    protected function resetForm(): void
    {
        $this->roleUuid = null;
        $this->name = '';
        $this->slug = '';
        $this->selectedPermissionItems = [];
        $this->replacementRoleUuid = null;
        $this->pendingDeleteUserCount = 0;
        $this->permissionSearch = '';
        $this->resetErrorBag();
    }

    protected function allPermissionItemIds(): array
    {
        return $this->permissions
            ->flatMap(fn (Permission $group) => $group->items->pluck('uuid'))
            ->map(fn ($uuid) => (string) $uuid)
            ->unique()
            ->values()
            ->all();
    }

    protected function filteredPermissionItemIds(): array
    {
        return $this->filteredPermissions
            ->flatMap(fn (Permission $group) => $group->items->pluck('uuid'))
            ->map(fn ($uuid) => (string) $uuid)
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveRoleByUuid(string $roleUuid): Role
    {
        /** @var Role $role */
        $role = Role::query()
            ->withoutGlobalScopes()
            ->where('uuid', $roleUuid)
            ->firstOrFail();

        return $role;
    }

    /**
     * @param  array<int, string>  $permissionItemUuids
     * @return array<int, int>
     */
    protected function resolvePermissionItemIds(array $permissionItemUuids): array
    {
        return PermissionItem::query()
            ->whereIn('uuid', $permissionItemUuids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function generateUniqueSlug(string $name, int $teamId): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'role';
        }

        $candidate = $base;
        $suffix = 2;

        // Avoid collisions with both team-specific roles and global roles because
        // lookups by slug are performed in a combined scope (global + team).
        while (Role::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($teamId): void {
                $q->whereNull('team_id')->orWhere('team_id', $teamId);
            })
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
