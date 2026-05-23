<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\GrantablePermissions;

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
                return ActionResult::error('Ovu ulogu nije moguće uređivati.');
            }
        }

        $teamId = (int) ($payload['team_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $payload['name'] = $name;
        $payload['label'] = trim((string) ($payload['label'] ?? $name));

        if ($role && (int) $role->team_id !== $teamId) {
            return ActionResult::error('Ovu ulogu nije moguće uređivati u trenutnom timu.');
        }

        if ($teamId > 0 && $name !== '') {
            $normalizedName = mb_strtolower($name);
            $duplicateExists = Role::query()
                ->withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('team_id', $teamId)
                ->when($role, fn ($query) => $query->whereKeyNot($role->getKey()))
                ->get(['id', 'name'])
                ->contains(fn (Role $existingRole): bool => mb_strtolower(trim((string) $existingRole->name)) === $normalizedName);

            if ($duplicateExists) {
                return ActionResult::error('Uloga s tim nazivom već postoji.');
            }
        }

        if ($permissionItemIds === []) {
            return ActionResult::error('Uloga mora imati barem jednu dozvolu.');
        }

        if (! app(GrantablePermissions::class)->canGrantAll(auth()->user(), $teamId, $permissionItemIds)) {
            return ActionResult::error('Ne možete dodijeliti dozvole koje nisu dio vaše uloge.');
        }

        DB::transaction(function () use (&$role, $payload, $permissionItemIds): void {
            if ($role) {
                $role->update($payload);
            } else {
                /** @var Role $createdRole */
                $createdRole = Role::query()->create($payload);
                $role = $createdRole;
            }

            $role->permissionItems()->sync($permissionItemIds);
        });

        return ActionResult::success('Uloga je spremljena.');
    }
}
