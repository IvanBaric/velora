<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\MembershipCreated;
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
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'user_id');
    }

    public function inviter(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'invited_by_user_id');
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

    public function hasPermissionTo(string $permissionCode): bool
    {
        return $this->hasPermission($permissionCode);
    }

    public function activate(): void
    {
        $this->forceFill([
            'status' => TeamMembershipStatus::Active,
            'joined_at' => $this->joined_at ?? now(),
        ])->save();
    }

    public function suspend(): void
    {
        $this->forceFill([
            'status' => TeamMembershipStatus::Suspended,
        ])->save();
    }

    public function revoke(): void
    {
        $this->forceFill([
            'status' => TeamMembershipStatus::Revoked,
        ])->save();
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
}
