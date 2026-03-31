<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IvanBaric\Velora\Models\Team;

trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::creating(function ($model): void {
            if (! $model->team_id && function_exists('team')) {
                $model->team_id = team()->getKey();
            }
        });

        static::addGlobalScope('team_context', function (Builder $builder): void {
            if (! function_exists('team')) {
                return;
            }

            $builder->where($builder->qualifyColumn('team_id'), team()->getKey());
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
