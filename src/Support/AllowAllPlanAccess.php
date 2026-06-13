<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Contracts\PlanAccess;

final class AllowAllPlanAccess implements PlanAccess
{
    public function enabled(Model $billable, string $feature): bool
    {
        return true;
    }

    public function value(Model $billable, string $feature): mixed
    {
        return null;
    }

    public function limit(Model $billable, string $feature): ?int
    {
        return null;
    }

    public function remaining(Model $billable, string $feature, int $used): ?int
    {
        return null;
    }

    public function assertEnabled(Model $billable, string $feature): void
    {
        //
    }

    public function assertWithinLimit(Model $billable, string $feature, int $used): void
    {
        //
    }
}
