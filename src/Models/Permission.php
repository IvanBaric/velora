<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IvanBaric\Velora\Traits\HasUuid;

class Permission extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'label',
        'description',
        'icon',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PermissionItem::class)->orderBy('sort_order');
    }
}
