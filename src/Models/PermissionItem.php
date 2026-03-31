<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use IvanBaric\Velora\Traits\HasUuid;

class PermissionItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'permission_id',
        'name',
        'slug',
        'code',
        'label',
        'description',
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

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission_items', 'permission_item_id', 'role_id')
            ->using(RolePermissionItem::class)
            ->withTimestamps();
    }
}
