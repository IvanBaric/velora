<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IvanBaric\Velora\Traits\HasUuid;

class Team extends Model
{
    use HasUuid;

    protected $table = 'teams';

    protected $guarded = [];

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsToMany($userModel, 'team_memberships', 'team_id', 'user_id')
            ->withPivot(['status', 'is_owner', 'joined_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
