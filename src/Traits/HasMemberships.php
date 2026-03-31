<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;

trait HasMemberships
{
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class, 'user_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_memberships', 'user_id', 'team_id')
            ->withPivot(['status', 'is_owner', 'joined_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function membershipForCurrentTeam(): ?TeamMembership
    {
        return $this->memberships()
            ->where('team_id', team()->getKey())
            ->first();
    }

    protected static function bootHasMemberships(): void
    {
        static::deleting(function (Model $user): void {
            if (! method_exists($user, 'memberships')) {
                return;
            }

            $user->memberships()->each(fn (TeamMembership $membership) => $membership->delete());
        });
    }
}
