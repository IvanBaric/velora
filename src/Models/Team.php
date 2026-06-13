<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use IvanBaric\Velora\Traits\HasUuid;

class Team extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'teams';

    protected $fillable = [];

    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $team): void {
            if (! $team->getAttribute('template')) {
                $team->setAttribute('template', 'aurora');
            }

            if (! $team->getAttribute('slug')) {
                $team->setAttribute('slug', $team->generateUniqueSlug((string) $team->getAttribute('name')));
            }

            $teamClass = velora_team_model();
            if (! $team->getAttribute('shortcode') && method_exists($teamClass, 'generateUniqueShortcode')) {
                $team->setAttribute('shortcode', $teamClass::generateUniqueShortcode());
            }
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(velora_user_model(), 'team_memberships', 'team_id', 'user_id')
            ->withPivot(['status', 'is_owner', 'joined_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
