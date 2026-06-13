<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\PlanFeatures;

final class SyncMembershipRoleAction
{
    public function execute(TeamMembership $membership, string $roleSlug): ActionResult
    {
        if ($membership->is_owner) {
            return ActionResult::error(__('Ulogu vlasnika nije moguće promijeniti.'));
        }

        if (! $this->rolesAndPermissionsAvailable($membership)) {
            return ActionResult::error(__('Roles and permissions are not included in your current plan. Existing roles stay active; upgrade your plan to change member roles.'));
        }

        $roleExists = Role::query()
            ->availableToTeam($membership->team_id)
            ->assignable()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $roleExists) {
            return ActionResult::error(__('Uloga nije pronađena za ovaj tim.'));
        }

        $result = $membership->syncRoles([$roleSlug]);

        if (! $result->ok) {
            return ActionResult::fromOperationResult($result);
        }

        return ActionResult::success(__('Uloga člana je ažurirana.'));
    }

    private function rolesAndPermissionsAvailable(TeamMembership $membership): bool
    {
        $team = $membership->team ?: velora_team_model()::query()->find($membership->team_id);

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
}
