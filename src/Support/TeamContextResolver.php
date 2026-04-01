<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Support\Facades\Auth;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\TeamSwitched;
use IvanBaric\Velora\Exceptions\UnableToResolveCurrentTeam;
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

        if ($team = $this->resolvePreferredTeam()) {
            return $this->resolved = $team;
        }

        return $this->resolved = $this->resolveFallbackTeam();
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

    protected function resolvePreferredTeam(): ?Team
    {
        if ($team = $this->resolveForAuthenticatedUser()) {
            return $team;
        }

        return $this->resolveFromPublicSession();
    }

    protected function resolveFallbackTeam(): Team
    {
        return match ($this->strategy()) {
            'first_team' => $this->resolveFirstTeamFallback(),
            'create_default_team' => $this->resolveDefaultTeamFallback(),
            'system_team_fallback' => $this->resolveSystemTeamFallback(),
            'strict' => throw new UnableToResolveCurrentTeam('Unable to resolve current team using strict strategy.'),
            default => throw new UnableToResolveCurrentTeam('Unsupported current team strategy ['.$this->strategy().'].'),
        };
    }

    protected function resolveFirstTeamFallback(): Team
    {
        $team = Team::query()->orderBy('id')->first();

        if (! $team) {
            throw new UnableToResolveCurrentTeam('No team exists for first_team fallback.');
        }

        $this->storeCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function resolveDefaultTeamFallback(): Team
    {
        $team = Team::query()->firstOrCreate([
            'name' => (string) config('velora.current_team.default_team_name', 'Default Team'),
        ]);

        $this->storeCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function resolveSystemTeamFallback(): Team
    {
        $team = new Team(['name' => (string) config('velora.current_team.system_team_name', 'System Team')]);
        $team->setAttribute('id', 0);

        return $team;
    }

    protected function strategy(): string
    {
        return (string) config('velora.current_team.strategy', 'strict');
    }
}
