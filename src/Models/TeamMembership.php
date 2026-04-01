<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\MembershipActivated;
use IvanBaric\Velora\Events\MembershipCreated;
use IvanBaric\Velora\Events\MembershipRevoked;
use IvanBaric\Velora\Events\MembershipSuspended;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Traits\BelongsToTeam;
use IvanBaric\Velora\Traits\HasTeamRolesPermissions;
use IvanBaric\Velora\Traits\HasUuid;

class TeamMembership extends Model
{
    use BelongsToTeam;
    use HasTeamRolesPermissions;
    use HasUuid;

    protected $table = 'team_memberships';

    protected $fillable = [
        'team_id',
        'user_id',
        'status',
        'is_owner',
        'invited_by_user_id',
        'invited_email',
        'joined_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamMembershipStatus::class,
            'is_owner' => 'boolean',
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(velora_user_model(), 'user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(velora_user_model(), 'invited_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TeamMembershipEvent::class, 'team_membership_id')->latest();
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTeam(Builder $query, int|string $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TeamMembershipStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === TeamMembershipStatus::Active;
    }

    public function canActivate(): bool
    {
        return $this->status !== TeamMembershipStatus::Revoked
            && $this->status !== TeamMembershipStatus::Active;
    }

    public function canSuspend(): bool
    {
        return $this->status === TeamMembershipStatus::Active;
    }

    public function canRevoke(): bool
    {
        return $this->status !== TeamMembershipStatus::Revoked;
    }

    public function hasPermissionTo(string $permissionCode): bool
    {
        return $this->hasPermission($permissionCode);
    }

    public function activate(?int $actorUserId = null): ActionResult
    {
        if (! $this->canActivate()) {
            return ActionResult::error($this->status === TeamMembershipStatus::Active
                ? 'Membership is already active.'
                : 'Revoked memberships cannot be reactivated.');
        }

        $this->forceFill([
            'status' => TeamMembershipStatus::Active,
            'joined_at' => $this->joined_at ?? now(),
        ])->save();

        $this->recordEvent('activated', $actorUserId);
        event(new MembershipActivated($this));

        return ActionResult::success('Membership activated.');
    }

    public function suspend(?int $actorUserId = null): ActionResult
    {
        if (! $this->canSuspend()) {
            return ActionResult::error('Membership cannot be suspended from its current status.');
        }

        $this->forceFill([
            'status' => TeamMembershipStatus::Suspended,
        ])->save();

        $this->recordEvent('suspended', $actorUserId);
        event(new MembershipSuspended($this));

        return ActionResult::success('Membership suspended.');
    }

    public function revoke(?int $actorUserId = null): ActionResult
    {
        if (! $this->canRevoke()) {
            return ActionResult::error('Membership is already revoked.');
        }

        $this->forceFill([
            'status' => TeamMembershipStatus::Revoked,
        ])->save();

        $this->recordEvent('revoked', $actorUserId);
        event(new MembershipRevoked($this));

        return ActionResult::success('Membership revoked.');
    }

    public static function ensureForUser(Model $user, Team $team, bool $isOwner = false): self
    {
        /** @var self $membership */
        $membership = static::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'team_id' => $team->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => TeamMembershipStatus::Active,
                    'is_owner' => $isOwner,
                    'joined_at' => now(),
                ],
            );

        if (! $membership->isActive()) {
            $membership->activate();
        }

        if ($isOwner && ! $membership->is_owner) {
            $membership->forceFill(['is_owner' => true])->save();
        }

        if ($membership->wasRecentlyCreated) {
            event(new MembershipCreated($membership));
        }

        return $membership;
    }

    public function recordEvent(string $type, ?int $actorUserId = null, array $meta = []): void
    {
        $this->events()->create([
            'team_id' => $this->team_id,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'meta' => $meta,
        ]);
    }
}
