<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PlanAccess
{
    public function enabled(Model $billable, string $feature): bool;

    public function value(Model $billable, string $feature): mixed;

    public function limit(Model $billable, string $feature): ?int;

    public function remaining(Model $billable, string $feature, int $used): ?int;

    public function assertEnabled(Model $billable, string $feature): void;

    public function assertWithinLimit(Model $billable, string $feature, int $used): void;
}
