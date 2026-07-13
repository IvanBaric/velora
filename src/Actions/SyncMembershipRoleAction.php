<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;

final class SyncMembershipRoleAction
{
    use AuthorizesActions;

    public function execute(TeamMembership $membership, string $roleSlug): ActionResult
    {
        if ($result = $this->authorizeVeloraAction(TeamPermissions::MANAGE_MEMBERS, $membership)) {
            return $result;
        }

        if (! $this->currentActorOwnsTeam((int) $membership->team_id)) {
            return ActionResult::error(__('Samo vlasnik organizacije može mijenjati uloge suradnika.'));
        }

        if ((int) $membership->user_id === (int) auth()->id()) {
            return ActionResult::error(__('Svoju ulogu nije moguće promijeniti na ovaj način.'));
        }

        if ($membership->isOwner()) {
            return ActionResult::error(__('Ulogu vlasnika nije moguće promijeniti.'));
        }

        if (! $this->rolesAndPermissionsAvailable($membership)) {
            return ActionResult::error(__('Uloge i dozvole nisu uključene u trenutačni plan. Postojeće uloge ostaju aktivne; nadogradite plan za promjenu uloga.'));
        }

        $roleExists = Role::query()
            ->availableToTeam($membership->team_id)
            ->assignable()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $roleExists) {
            return ActionResult::error(__('Uloga nije pronađena za ovu organizaciju.'));
        }

        $result = $membership->syncRoles([$roleSlug]);

        if (! $result->ok) {
            return ActionResult::fromOperationResult($result);
        }

        return ActionResult::success(__('Uloga suradnika je ažurirana.'));
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

    private function authorizeVeloraAction(string $ability, mixed $arguments = []): ?ActionResult
    {
        $result = $this->authorizeAction($ability, $arguments);

        return $result ? ActionResult::fromCorexis($result) : null;
    }

    private function currentActorOwnsTeam(int $teamId): bool
    {
        $user = auth()->user();

        return $user && method_exists($user, 'ownsTeam') && $user->ownsTeam($teamId);
    }
}
