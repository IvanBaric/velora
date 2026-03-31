<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IvanBaric\Velora\Traits\HasUuid;

class UserRole extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'team_id',
        'role_id',
        'assigned_by_user_id',
        'assigned_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'assigned_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
