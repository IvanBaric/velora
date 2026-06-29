<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TeamMembershipEvent extends Model
{
    protected $fillable = [
        'uuid',
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

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if (Schema::hasColumn($event->getTable(), 'uuid') && blank($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
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
