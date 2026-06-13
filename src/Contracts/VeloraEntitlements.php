<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Contracts;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Data\VeloraEntitlementDecision;

interface VeloraEntitlements
{
    public function canInviteTeamMember(Model $team): VeloraEntitlementDecision;

    public function canManageRolesAndPermissions(Model $team): VeloraEntitlementDecision;

    public function defaultPlanCode(): string;
}
