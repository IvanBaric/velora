<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::creating(function ($model): void {
            if (! $model->team_id && app()->bound('team') && velora_is_team_model(app('team'))) {
                $model->team_id = app('team')->getKey();
            }
        });

        static::addGlobalScope('team_context', function (Builder $builder): void {
            if (! app()->bound('team') || ! velora_is_team_model(app('team'))) {
                return;
            }

            $builder->where($builder->qualifyColumn('team_id'), app('team')->getKey());
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(velora_team_model());
    }
}
