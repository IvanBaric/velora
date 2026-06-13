<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Models\Team;

class TeamModelResolver
{
    /**
     * @return class-string<Model>
     */
    public function className(): string
    {
        $configured = config('velora.models.team');

        if (is_string($configured) && class_exists($configured)) {
            return $configured;
        }

        return Team::class;
    }

    public function instance(): Model
    {
        /** @var Model $team */
        $team = app($this->className());

        return $team;
    }

    public function query(): Builder
    {
        return $this->instance()->newQuery();
    }

    public function table(): string
    {
        return $this->instance()->getTable();
    }

    public function keyName(): string
    {
        return $this->instance()->getKeyName();
    }

    public function isTeam(mixed $value): bool
    {
        $className = $this->className();

        return $value instanceof $className;
    }

    public function bind(Model $team): void
    {
        app()->instance('team', $team);
        app()->instance($this->className(), $team);
        app()->instance(Team::class, $team);
    }
}
