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
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;

final class DeleteRoleAction
{
    use AuthorizesActions;

    public function execute(Role $role, ?Role $replacementRole = null): ActionResult
    {
        if ($result = $this->authorizeVeloraAction(TeamPermissions::MANAGE_ROLES, $role)) {
            return $result;
        }

        if ($role->isGlobal() || $role->is_locked) {
            return ActionResult::error(__('Ovu ulogu nije moguće izbrisati.'));
        }

        if (! $this->rolesAndPermissionsAvailable($role)) {
            return ActionResult::error(__('Uloge i dozvole nisu uključene u trenutačni plan. Postojeće uloge ostaju aktivne; nadogradite plan za upravljanje prilagođenim pristupom.'));
        }

        if ($replacementRole && (int) $replacementRole->team_id !== (int) $role->team_id && ! $replacementRole->isGlobal()) {
            return ActionResult::error(__('Zamjenska uloga mora pripadati istoj organizaciji.'));
        }

        $userCount = $role->userRoles()->count();
        if ($userCount > 0 && ! $replacementRole) {
            return ActionResult::error(__('Odaberite zamjensku ulogu.'));
        }

        DB::transaction(function () use ($role, $replacementRole, $userCount): void {
            /** @var Role $role */
            $role = Role::query()
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($replacementRole instanceof Role) {
                $replacementRole = Role::query()
                    ->whereKey($replacementRole->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($userCount > 0) {
                $role->userRoles()->update(['role_id' => $replacementRole?->getKey()]);
            }

            $role->permissionItems()->detach();
            $role->delete();
        });

        return ActionResult::success(__('Uloga je izbrisana.'));
    }

    private function rolesAndPermissionsAvailable(Role $role): bool
    {
        if (! $role->team_id) {
            return false;
        }

        $team = velora_team_model()::query()->find($role->team_id);

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
