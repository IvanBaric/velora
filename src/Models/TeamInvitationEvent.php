<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitationEvent extends Model
{
    protected $fillable = [
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
        return $this->belongsTo(Team::class);
    }

    public function actor(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('velora.models.user');

        return $this->belongsTo($userModel, 'actor_user_id');
    }
}
