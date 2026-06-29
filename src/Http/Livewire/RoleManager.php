<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IvanBaric\Velora\Actions\DeleteRoleAction;
use IvanBaric\Velora\Actions\SaveRoleAction;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\GrantablePermissions;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RoleManager extends Component
{
    use InteractsWithActionResults;

    public bool $isOpen = false;

    public bool $isFormOpen = false;

    public bool $isDeleteConfirmOpen = false;

    #[Locked]
    public bool $isReadOnly = false;

    #[Locked]
    public ?string $roleUuid = null;

    public string $name = '';

    #[Locked]
    public string $slug = '';

    public array $selectedPermissionItems = [];

    public ?string $replacementRoleUuid = null;

    #[Locked]
    public int $pendingDeleteUserCount = 0;

    public string $permissionSearch = '';

    #[On('open-role-manager')]
    public function open(): void
    {
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        $this->resetForm();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function createRole(): void
    {
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $this->resetForm();
        $this->isFormOpen = true;
    }

    public function editRole(string $roleUuid): void
    {
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $role = $this->resolveRoleByUuid($roleUuid);
        $this->openRoleForm($role, $role->isGlobal() || $role->is_locked);
    }

    public function viewRole(string $roleUuid): void
    {
        $role = $this->resolveRoleByUuid($roleUuid);

        $this->openRoleForm($role, true);
    }

    public function save(SaveRoleAction $saveRole): void
    {
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        if ($this->isReadOnly) {
            $this->toastFromResult(ActionResult::error(__('Ova uloga je samo za čitanje.')));

            return;
        }

        $teamId = (int) team()->getKey();
        $role = $this->roleUuid ? $this->resolveRoleByUuid($this->roleUuid) : null;
        $this->name = $this->normalizeRoleName($this->name);

        if ($this->selectedPermissionItems === []) {
            $this->toastFromResult(ActionResult::error(__('Odaberite barem jednu dozvolu za ovu ulogu.')));

            return;
        }

        $data = $this->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail) use ($role, $teamId): void {
                        $normalizedName = mb_strtolower($this->normalizeRoleName((string) $value));
                        $duplicateExists = Role::query()
                            ->withoutGlobalScopes()
                            ->whereNull('deleted_at')
                            ->where('team_id', $teamId)
                            ->when($role, fn ($query) => $query->whereKeyNot($role->getKey()))
                            ->get(['id', 'name'])
                            ->contains(fn (Role $existingRole): bool => mb_strtolower($this->normalizeRoleName((string) $existingRole->name)) === $normalizedName);

                        if ($duplicateExists) {
                            $fail(__('Uloga s tim nazivom već postoji.'));
                        }
                    },
                ],
                'selectedPermissionItems' => ['required', 'array', 'min:1'],
            ],
            [
                'selectedPermissionItems.required' => __('Odaberite barem jednu dozvolu za ovu ulogu.'),
                'selectedPermissionItems.min' => __('Odaberite barem jednu dozvolu za ovu ulogu.'),
            ],
        );

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

        $permissionItemIds = $this->resolvePermissionItemIds($this->selectedPermissionItems);
        if (count($permissionItemIds) !== count(array_unique($this->selectedPermissionItems))) {
            $this->toastFromResult(ActionResult::error(__('Odabrane dozvole nisu važeće.')));

            return;
        }

        $result = $saveRole->execute($role, $payload, $permissionItemIds);

        $this->resetForm();
        $this->isFormOpen = false;
        $this->toastFromResult($result);
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
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $role = $this->resolveRoleByUuid($roleUuid);
        abort_if($role->isGlobal() || $role->is_locked, 403);

        $this->roleUuid = (string) $role->uuid;
        $this->replacementRoleUuid = null;
        $this->pendingDeleteUserCount = (int) $role->userRoles()->count();
        $this->isDeleteConfirmOpen = true;
    }

    public function deleteRole(DeleteRoleAction $deleteRole): void
    {
        if (! $this->authorizeOrToast(TeamPermissions::MANAGE_ROLES)) {
            return;
        }

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $role = $this->resolveRoleByUuid((string) $this->roleUuid);
        $replacementRole = $this->replacementRoleUuid
            ? $this->resolveRoleByUuid((string) $this->replacementRoleUuid)
            : null;
        $result = $deleteRole->execute($role, $replacementRole);

        if (! $result->success) {
            $this->addError('replacementRoleUuid', $result->message);

            return;
        }

        $this->isDeleteConfirmOpen = false;
        $this->pendingDeleteUserCount = 0;
        $this->resetForm();
        $this->toastFromResult($result);
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
        $grantablePermissionIds = app(GrantablePermissions::class)->idsFor(auth()->user(), (int) team()->getKey());

        return Permission::query()
            ->where('is_active', true)
            ->with(['items' => fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('id', $grantablePermissionIds)
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Permission $permission) => $permission->items->isNotEmpty())
            ->values();
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
        $roleManagementBlockedMessage = $this->roleManagementBlockedMessage();

        return view('velora::livewire.role-manager', [
            'roles' => $this->roles,
            'permissions' => $this->filteredPermissions,
            'rolesAndPermissionsAvailable' => $roleManagementBlockedMessage === null,
            'roleManagementBlockedMessage' => $roleManagementBlockedMessage,
        ]);
    }

    protected function rolesAndPermissionsAvailable(bool $toast = true): bool
    {
        $roleManagementBlockedMessage = $this->roleManagementBlockedMessage();
        if ($roleManagementBlockedMessage === null) {
            return true;
        }

        if ($toast) {
            $this->toastFromResult(ActionResult::error($roleManagementBlockedMessage));
        }

        return false;
    }

    protected function roleManagementBlockedMessage(): ?string
    {
        try {
            app(PlanAccess::class)->assertEnabled(team(), PlanFeatures::ROLES_AND_PERMISSIONS);

            return null;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException) {
            return __('Nove prilagođene uloge dostupne su na Pro planu. Postojeće uloge ostaju aktivne.');
        }
    }

    protected function resetForm(): void
    {
        $this->roleUuid = null;
        $this->name = '';
        $this->slug = '';
        $this->isReadOnly = false;
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
            ->where(function ($query): void {
                $query->whereNull('team_id')
                    ->orWhere('team_id', team()->getKey());
            })
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
            ->where('is_active', true)
            ->whereHas('permission', fn ($query) => $query->where('is_active', true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
    }

    protected function normalizeRoleName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    protected function generateUniqueSlug(string $name, int $teamId): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'role';
        }

        $candidate = $base;
        $suffix = 2;

        while (Role::query()
            ->withoutGlobalScopes()
            ->where(function ($query) use ($teamId): void {
                $query->whereNull('team_id')->orWhere('team_id', $teamId);
            })
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function openRoleForm(Role $role, bool $readOnly): void
    {
        $this->resetForm();
        $this->roleUuid = (string) $role->uuid;
        $this->name = (string) $role->name;
        $this->slug = (string) $role->slug;
        $this->isReadOnly = $readOnly;
        $this->selectedPermissionItems = $role->permissionItems()
            ->pluck('permission_items.uuid')
            ->map(fn ($uuid) => (string) $uuid)
            ->all();
        $this->isFormOpen = true;
    }
}
