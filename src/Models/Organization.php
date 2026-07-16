<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use IvanBaric\Corexis\Concerns\HasUniqueSlug;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Velora\Traits\HasUuid;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $uuid
 * @property string $slug
 * @property array<string, mixed>|string $name
 * @property array<string, mixed>|string|null $description
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property string|null $city
 * @property string|null $postal_code
 * @property int|null $founded_year
 * @property string|null $logo
 * @property string|null $cover_image
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 */
class Organization extends Model
{
    use HasUniqueSlug, HasUuid, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $organization): void {
            if ($organization->getAttribute('team_id') !== null) {
                return;
            }

            $teamId = app(TenantResolver::class)->id();
            $organization->setAttribute('team_id', is_numeric($teamId) ? (int) $teamId : null);
        });
    }

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'founded_year' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function slugSource(): string
    {
        return $this->localized('name');
    }

    /** @return BelongsTo<Model, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(velora_team_model(), 'team_id');
    }

    /** @param Builder<Organization> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Organization> $query */
    #[Scope]
    protected function forTeam(Builder $query, ?int $teamId): void
    {
        $teamId === null
            ? $query->whereNull('team_id')
            : $query->where('team_id', $teamId);
    }

    public static function findByUuid(string $uuid): ?static
    {
        return static::query()->where('uuid', $uuid)->first();
    }

    public function localized(string $field, ?string $locale = null): string
    {
        $value = $this->getAttribute($field);

        if (! is_array($value)) {
            return (string) $value;
        }

        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallback] ?? reset($value) ?: '');
    }
}
