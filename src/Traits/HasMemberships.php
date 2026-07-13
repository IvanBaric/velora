<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\TeamMembership;

trait HasMemberships
{
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class, 'user_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(velora_team_model(), 'team_memberships', 'user_id', 'team_id')
            ->withPivot(['status', 'is_owner', 'joined_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function membershipForCurrentTeam(): ?TeamMembership
    {
        return $this->memberships()
            ->where('team_id', team()->getKey())
            ->first();
    }

    public function membershipForTeam(Model|int|string|null $team = null): ?TeamMembership
    {
        return $this->memberships()
            ->withoutGlobalScopes()
            ->where('team_id', $this->resolveMembershipTeamId($team))
            ->first();
    }

    public function ownsTeam(Model|int|string|null $team = null): bool
    {
        return $this->memberships()
            ->withoutGlobalScopes()
            ->where('team_id', $this->resolveMembershipTeamId($team))
            ->where('is_owner', true)
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();
    }

    public function setTeamOwner(Model|int|string|null $team = null): TeamMembership
    {
        return TeamMembership::ensureForUser($this, $this->resolveMembershipTeam($team), true);
    }

    public function unsetTeamOwner(Model|int|string|null $team = null): ?TeamMembership
    {
        return $this->removeTeamOwner($team);
    }

    public function removeTeamOwner(Model|int|string|null $team = null): ?TeamMembership
    {
        $membership = $this->membershipForTeam($team);

        if ($membership instanceof TeamMembership) {
            $membership->revokeOwnerAccess();
        }

        return $membership;
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

    protected function resolveMembershipTeam(Model|int|string|null $team = null): Model
    {
        if ($team instanceof Model) {
            return $team;
        }

        if (is_int($team) || (is_string($team) && ctype_digit($team))) {
            return velora_team_query()->findOrFail((int) $team);
        }

        return team();
    }

    protected function resolveMembershipTeamId(Model|int|string|null $team = null): int
    {
        if ($team instanceof Model) {
            return (int) $team->getKey();
        }

        if (is_int($team) || (is_string($team) && ctype_digit($team))) {
            return (int) $team;
        }

        return (int) team()->getKey();
    }
}
