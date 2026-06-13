<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class TeamPlanUsage
{
    public static function members(Model $team): int
    {
        if (! method_exists($team, 'memberships')) {
            return 0;
        }

        $relation = $team->memberships();

        return $relation instanceof Relation ? (int) $relation->getQuery()->count() : 0;
    }
}
