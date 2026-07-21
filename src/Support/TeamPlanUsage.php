<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\TeamInvitation;

final class TeamPlanUsage
{
    public static function members(Model $team): int
    {
        if (! method_exists($team, 'memberships')) {
            return 0;
        }

        $relation = $team->memberships();

        return $relation instanceof Relation
            ? (int) $relation->getQuery()->where('status', TeamMembershipStatus::Active->value)->count()
            : 0;
    }

    public static function occupiedMemberSeats(Model $team): int
    {
        return self::members($team) + self::pendingInvitations($team);
    }

    public static function pendingInvitations(Model $team): int
    {
        return TeamInvitation::query()
            ->withoutGlobalScopes()
            ->where('team_id', $team->getKey())
            ->where('status', TeamInvitationStatus::Pending->value)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}
