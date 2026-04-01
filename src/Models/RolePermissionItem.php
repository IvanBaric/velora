<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use IvanBaric\Velora\Traits\HasUuid;

class RolePermissionItem extends Pivot
{
    use HasUuid;

    protected $table = 'role_permission_items';
}
