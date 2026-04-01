<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMembershipEvent extends Model
{
    protected $fillable = [
        'team_membership_id',
        'team_id',
        'actor_user_id',
        'type',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(TeamMembership::class, 'team_membership_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(velora_user_model(), 'actor_user_id');
    }
}
