<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IvanBaric\Velora\Traits\HasUuid;

class TeamInvitationEvent extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'team_invitation_id',
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

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(TeamInvitation::class, 'team_invitation_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(velora_team_model());
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(velora_user_model(), 'actor_user_id');
    }
}
