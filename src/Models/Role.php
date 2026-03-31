<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use IvanBaric\Velora\Traits\HasUuid;

class Role extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'label',
        'description',
        'redirect_to',
        'is_system',
        'is_locked',
        'assignable',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_locked' => 'boolean',
            'assignable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function permissionItems(): BelongsToMany
    {
        return $this->belongsToMany(PermissionItem::class, 'role_permission_items', 'role_id', 'permission_item_id')
            ->using(RolePermissionItem::class)
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsToMany($userModel, 'user_roles', 'role_id', 'user_id')
            ->withPivot(['team_id', 'assigned_by_user_id', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('team_id');
    }

    public function scopeTeam(Builder $query, int|string|null $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeAvailableToTeam(Builder $query, int|string|null $teamId = null): Builder
    {
        $teamId ??= function_exists('team') ? team()->getKey() : null;

        return $query
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($teamId): void {
                $builder->whereNull('team_id');

                if ($teamId) {
                    $builder->orWhere('team_id', $teamId);
                }
            });
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('assignable', true)->where('is_active', true);
    }

    public function scopeNotHidden(Builder $query): Builder
    {
        return $query->whereNotIn('slug', (array) config('velora.roles.hidden', []));
    }

    public static function getDefault(int|string|null $teamId = null): ?self
    {
        $teamId ??= function_exists('team') ? team()->getKey() : null;
        $defaultSlug = (string) config('velora.roles.default_member_slug', 'member');

        return static::query()
            ->availableToTeam($teamId)
            ->where('slug', $defaultSlug)
            ->orderByRaw('case when team_id is null then 1 else 0 end')
            ->first();
    }

    public function isGlobal(): bool
    {
        return $this->team_id === null;
    }

    public function isLockedSystemRole(): bool
    {
        return $this->is_system && $this->is_locked;
    }

    public function getAbbrvAttribute(): string
    {
        return (string) $this->slug;
    }
}
