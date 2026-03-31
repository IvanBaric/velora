<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Support\Facades\Auth;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\TeamSwitched;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;

class TeamContextResolver
{
    protected ?Team $resolved = null;

    public function resolve(): Team
    {
        if ($this->resolved instanceof Team) {
            return $this->resolved;
        }

        try {
            if ($team = $this->resolveForAuthenticatedUser()) {
                return $this->resolved = $team;
            }

            if ($team = $this->resolveFromPublicSession()) {
                return $this->resolved = $team;
            }

            if ($team = Team::query()->orderBy('id')->first()) {
                $this->storeCurrentTeamId((int) $team->getKey());

                return $this->resolved = $team;
            }

            $team = Team::query()->create(['name' => 'Default Team']);
            $this->storeCurrentTeamId((int) $team->getKey());

            return $this->resolved = $team;
        } catch (\Throwable) {
            $team = new Team(['name' => 'System Team']);
            $team->setAttribute('id', 0);

            return $this->resolved = $team;
        }
    }

    public function setCurrentTeam(Team|int $team): ?Team
    {
        $teamModel = $team instanceof Team
            ? $team
            : Team::query()->find($team);

        if (! $teamModel) {
            return null;
        }

        $this->storeCurrentTeamId((int) $teamModel->getKey());
        $this->resolved = $teamModel;

        app()->instance('team', $teamModel);
        app()->instance(Team::class, $teamModel);
        event(new TeamSwitched($teamModel));

        return $teamModel;
    }

    protected function resolveForAuthenticatedUser(): ?Team
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        if ($team = $this->resolveFromSessionMembership($user)) {
            return $team;
        }

        if ($team = $this->resolveFromMemberships($user)) {
            $this->storeCurrentTeamId((int) $team->getKey());

            return $team;
        }

        return $this->resolveFromLegacyUserTeam($user);
    }

    protected function resolveFromSessionMembership(mixed $user): ?Team
    {
        $teamId = $this->currentTeamIdFromSession();
        if (! $teamId) {
            return null;
        }

        if (! $this->userHasMembershipForTeam($user, $teamId)) {
            return null;
        }

        return Team::query()->find($teamId);
    }

    protected function resolveFromMemberships(mixed $user): ?Team
    {
        if (! method_exists($user, 'memberships')) {
            return null;
        }

        $membership = $user->memberships()
            ->withoutGlobalScopes()
            ->where('status', TeamMembershipStatus::Active->value)
            ->orderByDesc('is_owner')
            ->orderBy('id')
            ->first();

        if (! $membership) {
            return null;
        }

        return Team::query()->find($membership->team_id);
    }

    protected function resolveFromLegacyUserTeam(mixed $user): ?Team
    {
        $legacyTeamId = $user->team_id ?? null;
        if (! $legacyTeamId) {
            return null;
        }

        $team = Team::query()->find($legacyTeamId);
        if (! $team) {
            return null;
        }

        $this->storeCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function resolveFromPublicSession(): ?Team
    {
        $teamId = $this->currentTeamIdFromSession();
        if (! $teamId) {
            return null;
        }

        return Team::query()->find($teamId);
    }

    protected function userHasMembershipForTeam(mixed $user, int $teamId): bool
    {
        if (method_exists($user, 'memberships')) {
            return $user->memberships()
                ->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->where('status', TeamMembershipStatus::Active->value)
                ->exists();
        }

        return TeamMembership::query()
            ->where('user_id', $user->getKey())
            ->where('team_id', $teamId)
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();
    }

    protected function currentTeamIdFromSession(): ?int
    {
        try {
            if (! app()->bound('session')) {
                return null;
            }

            $teamId = app('session')->get((string) config('velora.session_key', 'velora.current_team_id'));

            return $teamId ? (int) $teamId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function storeCurrentTeamId(int $teamId): void
    {
        try {
            if (app()->bound('session')) {
                app('session')->put((string) config('velora.session_key', 'velora.current_team_id'), $teamId);
            }
        } catch (\Throwable) {
            // Session writes can fail in CLI contexts.
        }
    }
}
