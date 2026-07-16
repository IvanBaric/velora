<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class OrganizationModelResolver
{
    /** @return class-string<Model> */
    public function className(): string
    {
        return VeloraConfigResolver::organizationModel();
    }

    public function instance(): Model
    {
        return app($this->className());
    }

    public function query(): Builder
    {
        return $this->instance()->newQuery();
    }

    public function table(): string
    {
        return $this->instance()->getTable();
    }
}
