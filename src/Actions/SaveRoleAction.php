<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\ActionResult;

final class SaveRoleAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $permissionItemIds
     */
    public function execute(?Role $role, array $payload, array $permissionItemIds): ActionResult
    {
        if ($role) {
            if ($role->isGlobal() || $role->is_locked) {
                return ActionResult::error('This role cannot be edited.');
            }

            $role->update($payload);
        } else {
            /** @var Role $role */
            $role = Role::query()->create($payload);
        }

        $role->permissionItems()->sync($permissionItemIds);

        return ActionResult::success('Role saved.');
    }
}
