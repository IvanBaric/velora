<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\UserRole;

class RoleAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public UserRole $userRole,
        public Role $role,
    ) {}
}
