<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\GrantablePermissions;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;

final class SaveRoleAction
{
    use AuthorizesActions;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $permissionItemIds
     */
    public function execute(?Role $role, array $payload, array $permissionItemIds): ActionResult
    {
        $teamId = (int) ($payload['team_id'] ?? 0);

        if ($result = $this->authorizeVeloraAction(TeamPermissions::MANAGE_ROLES, $teamId)) {
            return $result;
        }

        if ($role && ($role->isGlobal() || $role->is_locked)) {
            return ActionResult::error(__('Ovu ulogu nije moguće uređivati.'));
        }

        if (! $this->rolesAndPermissionsAvailable($teamId)) {
            return ActionResult::error(__('Uloge i dozvole nisu uključene u trenutačni plan. Postojeće uloge ostaju aktivne; nadogradite plan za upravljanje prilagođenim pristupom.'));
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $payload['name'] = $name;
        $payload['label'] = trim((string) ($payload['label'] ?? $name));

        if ($role && (int) $role->team_id !== $teamId) {
            return ActionResult::error(__('Ovu ulogu nije moguće uređivati u trenutnoj organizaciji.'));
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
                return ActionResult::error(__('Uloga s tim nazivom već postoji.'));
            }
        }

        if ($permissionItemIds === []) {
            return ActionResult::error(__('Uloga mora imati barem jednu dozvolu.'));
        }

        if (! app(GrantablePermissions::class)->canGrantAll(auth()->user(), $teamId, $permissionItemIds)) {
            return ActionResult::error(__('Ne možete dodijeliti dozvole koje nisu dio vaše uloge.'));
        }

        DB::transaction(function () use (&$role, $payload, $permissionItemIds): void {
            if ($role) {
                $role = Role::query()
                    ->whereKey($role->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $role->update($payload);
            } else {
                /** @var Role $createdRole */
                $createdRole = Role::query()->create($payload);
                $role = $createdRole;
            }

            $role->permissionItems()->sync($permissionItemIds);
        });

        return ActionResult::success(__('Uloga je spremljena.'));
    }

    private function rolesAndPermissionsAvailable(int $teamId): bool
    {
        if ($teamId <= 0) {
            return false;
        }

        $teamModel = velora_team_model();
        $team = $teamModel::query()->find($teamId);

        if (! $team) {
            return false;
        }

        try {
            app(PlanAccess::class)->assertEnabled($team, PlanFeatures::ROLES_AND_PERMISSIONS);

            return true;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException) {
            return false;
        }
    }

    private function authorizeVeloraAction(string $ability, mixed $arguments = []): ?ActionResult
    {
        $result = $this->authorizeAction($ability, $arguments);

        return $result ? ActionResult::fromCorexis($result) : null;
    }
}
