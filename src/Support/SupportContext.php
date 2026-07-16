<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final readonly class SupportContext
{
    public function __construct(private TeamModelResolver $teams) {}

    public function activeFor(mixed $actor): bool
    {
        return (bool) config('velora.support_mode.enabled', false)
            && $this->isSuperadmin($actor)
            && $this->teamIdFor($actor) !== null;
    }

    public function teamFor(mixed $actor): ?Model
    {
        if (! $this->activeFor($actor)) {
            return null;
        }

        $team = $this->teams->instance();
        $schema = Schema::connection($team->getConnection()->getName());

        if (! $schema->hasTable($team->getTable())) {
            return null;
        }

        return $team->newQuery()->find($this->teamIdFor($actor));
    }

    public function teamIdFor(mixed $actor): int|string|null
    {
        $attribute = config('velora.support_mode.team_id_attribute')
            ?: config('velora.current_team.user_team_id_column', 'current_team_id');
        $teamId = is_string($attribute) && $attribute !== ''
            ? data_get($actor, $attribute)
            : null;

        if (is_int($teamId)) {
            return $teamId > 0 ? $teamId : null;
        }

        if (! is_string($teamId)) {
            return null;
        }

        $teamId = trim($teamId);

        return $teamId !== '' && $teamId !== '0' ? $teamId : null;
    }

    private function isSuperadmin(mixed $actor): bool
    {
        $attribute = config('velora.support_mode.superadmin_attribute')
            ?: config('velora.access.superadmin_attribute');

        return is_string($attribute)
            && $attribute !== ''
            && (bool) data_get($actor, $attribute);
    }
}
