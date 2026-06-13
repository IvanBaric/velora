<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Contracts\VeloraEntitlements;
use IvanBaric\Velora\Data\VeloraEntitlementDecision;

final class AllowsAllVeloraEntitlements implements VeloraEntitlements
{
    public function canInviteTeamMember(Model $team): VeloraEntitlementDecision
    {
        return VeloraEntitlementDecision::allow();
    }

    public function canManageRolesAndPermissions(Model $team): VeloraEntitlementDecision
    {
        return VeloraEntitlementDecision::allow();
    }

    public function defaultPlanCode(): string
    {
        return 'starter';
    }
}
