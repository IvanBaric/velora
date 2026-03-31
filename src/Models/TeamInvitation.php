<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Traits\BelongsToTeam;
use IvanBaric\Velora\Traits\HasUuid;
use Carbon\CarbonInterface;

class TeamInvitation extends Model
{
    use BelongsToTeam;
    use HasUuid;

    protected $fillable = [
        'team_id',
        'email',
        'role_id',
        'role_slug',
        'status',
        'invited_by_user_id',
        'token_hash',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'last_sent_at',
        'resent_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamInvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'resent_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation): void {
            $invitation->email = self::normalizeEmail((string) $invitation->email);
            $invitation->status ??= TeamInvitationStatus::Pending;
            $invitation->last_sent_at ??= now();
            $invitation->expires_at ??= self::defaultExpiresAt();
            $invitation->role_slug ??= self::defaultRoleSlug($invitation->team_id);
        });

        static::updating(function (self $invitation): void {
            if ($invitation->isDirty('email')) {
                $invitation->email = self::normalizeEmail((string) $invitation->email);
            }
        });

        static::created(function (self $invitation): void {
            $invitation->recordEvent('created', $invitation->invited_by_user_id, [
                'role_slug' => $invitation->role_slug,
            ]);
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'invited_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TeamInvitationEvent::class, 'team_invitation_id')->latest();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TeamInvitationStatus::Pending->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TeamInvitationStatus::Pending->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForPlainToken(Builder $query, string $plainToken): Builder
    {
        return $query->where('token_hash', hash('sha256', $plainToken));
    }

    public function isExpired(): bool
    {
        return $this->status === TeamInvitationStatus::Expired
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function markExpired(?int $actorUserId = null, array $meta = []): void
    {
        if ($this->status !== TeamInvitationStatus::Pending) {
            return;
        }

        $this->forceFill(['status' => TeamInvitationStatus::Expired])->save();
        $this->recordEvent('expired', $actorUserId, $meta);
    }

    public function markAccepted(?int $actorUserId = null, array $meta = []): void
    {
        $this->forceFill([
            'status' => TeamInvitationStatus::Accepted,
            'accepted_at' => now(),
        ])->save();

        $this->recordEvent('accepted', $actorUserId, $meta);
    }

    public function markRevoked(?int $actorUserId = null, array $meta = []): void
    {
        $this->forceFill([
            'status' => TeamInvitationStatus::Revoked,
            'revoked_at' => now(),
        ])->save();

        $this->recordEvent('revoked', $actorUserId, $meta);
    }

    public function issueToken(): string
    {
        $plainToken = Str::random(40);

        $this->forceFill([
            'token_hash' => hash('sha256', $plainToken),
        ])->save();

        return $plainToken;
    }

    public function matchesToken(string $plainToken): bool
    {
        if (! $this->token_hash) {
            return false;
        }

        return hash_equals((string) $this->token_hash, hash('sha256', $plainToken));
    }

    public function prepareForResend(?int $invitedBy = null, ?string $roleSlug = null): string
    {
        $plainToken = Str::random(40);

        $this->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'status' => TeamInvitationStatus::Pending,
            'invited_by_user_id' => $invitedBy,
            'role_slug' => $roleSlug ?? $this->role_slug ?? self::defaultRoleSlug($this->team_id),
            'expires_at' => self::defaultExpiresAt(),
            'accepted_at' => null,
            'revoked_at' => null,
            'last_sent_at' => now(),
            'resent_count' => (int) $this->resent_count + 1,
        ])->save();

        $this->recordEvent('resent', $invitedBy, [
            'role_slug' => $this->role_slug,
            'resent_count' => (int) $this->resent_count,
        ]);

        return $plainToken;
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

    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public static function defaultExpiresAt(): CarbonInterface
    {
        return now()->addDays((int) config('velora.invitations.expires_after_days', 7));
    }

    public static function defaultRoleSlug(int|string|null $teamId = null): ?string
    {
        return Role::getDefault($teamId)?->slug;
    }

    public function getRoleAbbrvAttribute(): ?string
    {
        return $this->role_slug;
    }
}
