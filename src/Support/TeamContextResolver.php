<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use IvanBaric\Velora\Actions\CreatePersonalTeam;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\TeamSwitched;
use IvanBaric\Velora\Exceptions\UnableToResolveCurrentTeam;
use IvanBaric\Velora\Models\TeamMembership;

class TeamContextResolver
{
    protected ?Model $resolved = null;

    public function __construct(
        protected TeamModelResolver $teams,
    ) {}

    public function resolve(): Model
    {
        if ($this->resolved instanceof Model) {
            return $this->resolved;
        }

        if ($team = $this->resolvePreferredTeam()) {
            return $this->resolved = $team;
        }

        return $this->resolved = $this->resolveFallbackTeam();
    }

    public function setCurrentTeam(Model|int|string $team): ?Model
    {
        $teamModel = $this->teams->isTeam($team)
            ? $team
            : $this->teams->query()->find($team);

        if (! $teamModel instanceof Model) {
            return null;
        }

        $user = Auth::user();
        if ($user && ! $this->userCanUseTeam($user, (int) $teamModel->getKey())) {
            return null;
        }

        $this->persistCurrentTeamId((int) $teamModel->getKey());
        $this->resolved = $teamModel;

        $this->teams->bind($teamModel);
        event(new TeamSwitched($teamModel));

        return $teamModel;
    }

    protected function resolveForAuthenticatedUser(): ?Model
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        if ($team = $this->resolveFromCurrentTeam($user)) {
            return $team;
        }

        if ($team = $this->resolveFromMemberships($user)) {
            $this->persistCurrentTeamId((int) $team->getKey());

            return $team;
        }

        return $this->resolveMissingPersonalTeam($user);
    }

    protected function resolveFromCurrentTeam(mixed $user): ?Model
    {
        $teamId = (int) ($user->current_team_id ?? 0);
        if (! $teamId) {
            return null;
        }

        if (! $this->userCanUseTeam($user, $teamId)) {
            return null;
        }

        return $this->teams->query()->find($teamId);
    }

    protected function resolveFromMemberships(mixed $user): ?Model
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

        return $this->teams->query()->find($membership->team_id);
    }

    protected function resolveMissingPersonalTeam(mixed $user): ?Model
    {
        if (! config('velora.create_personal_team_when_missing', true) || ! $user instanceof Model) {
            return null;
        }

        $team = app(CreatePersonalTeam::class)->execute($user);
        $this->persistCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function userCanUseTeam(mixed $user, int $teamId): bool
    {
        $superadminAttribute = config('velora.access.superadmin_attribute');

        if (is_string($superadminAttribute) && $superadminAttribute !== '' && (bool) data_get($user, $superadminAttribute)) {
            return $this->teams->query()->whereKey($teamId)->exists();
        }

        return $this->userHasMembershipForTeam($user, $teamId);
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

    protected function persistCurrentTeamId(int $teamId): void
    {
        $user = Auth::user();
        if (! $user instanceof Model) {
            return;
        }

        try {
            $column = (string) config('velora.current_team.user_team_id_column', 'current_team_id');

            if ($column === '' || ! $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), $column)) {
                return;
            }

            if ((int) ($user->getAttribute($column) ?? 0) === $teamId) {
                return;
            }

            $user->forceFill([$column => $teamId])->saveQuietly();

            $relation = config('velora.current_team.user_team_relation');
            if (is_string($relation) && $relation !== '') {
                $user->unsetRelation($relation);
            }
        } catch (\Throwable) {
            // Some host applications may use a read-only or custom user model.
        }
    }

    protected function resolvePreferredTeam(): ?Model
    {
        if ($team = $this->resolveForAuthenticatedUser()) {
            return $team;
        }

        return null;
    }

    protected function resolveFallbackTeam(): Model
    {
        return match ($this->strategy()) {
            'first_team' => $this->resolveFirstTeamFallback(),
            'create_default_team' => $this->resolveDefaultTeamFallback(),
            'system_team_fallback' => $this->resolveSystemTeamFallback(),
            'strict' => throw new UnableToResolveCurrentTeam(__('Nije moguće odrediti trenutni tim koristeći strict strategiju.')),
            default => throw new UnableToResolveCurrentTeam(__('Nepodržana strategija trenutnog tima [:strategy].', ['strategy' => $this->strategy()])),
        };
    }

    protected function resolveFirstTeamFallback(): Model
    {
        $team = $this->teams->query()->orderBy('id')->first();

        if (! $team) {
            throw new UnableToResolveCurrentTeam(__('Ne postoji tim za first_team fallback.'));
        }

        $this->persistCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function resolveDefaultTeamFallback(): Model
    {
        $team = $this->teams->query()->firstOrCreate(
            ['name' => (string) config('velora.current_team.default_team_name', __('Zadani tim'))],
            $this->teamCreateDefaults(),
        );

        $this->persistCurrentTeamId((int) $team->getKey());

        return $team;
    }

    protected function resolveSystemTeamFallback(): Model
    {
        $teamClass = $this->teams->className();
        $team = new $teamClass(['name' => (string) config('velora.current_team.system_team_name', __('Sistemski tim'))]);
        $team->setAttribute('id', 0);

        return $team;
    }

    protected function teamCreateDefaults(): array
    {
        $defaults = [];
        $table = $this->teams->table();

        try {
            $schema = $this->teams->instance()->getConnection()->getSchemaBuilder();

            foreach ((array) config('velora.current_team.default_attributes', []) as $column => $value) {
                if (! is_string($column) || $column === '' || ! $schema->hasColumn($table, $column)) {
                    continue;
                }

                $defaults[$column] = $value;
            }
        } catch (\Throwable) {
            return [];
        }

        return $defaults;
    }

    protected function strategy(): string
    {
        return (string) config('velora.current_team.strategy', 'strict');
    }
}
