<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\ActionResult;

final class DeleteRoleAction
{
    public function execute(Role $role, ?Role $replacementRole = null): ActionResult
    {
        if ($role->isGlobal() || $role->is_locked) {
            return ActionResult::error('Ovu ulogu nije moguće izbrisati.');
        }

        $userCount = $role->userRoles()->count();
        if ($userCount > 0 && ! $replacementRole) {
            return ActionResult::error('Odaberite zamjensku ulogu.');
        }

        DB::transaction(function () use ($role, $replacementRole, $userCount): void {
            if ($userCount > 0) {
                $role->userRoles()->update(['role_id' => $replacementRole?->getKey()]);
            }

            $role->permissionItems()->detach();
            $role->delete();
        });

        return ActionResult::success('Uloga je izbrisana.');
    }
}
