<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserModelResolver
{
    /**
     * @return class-string<Model>
     */
    public function className(): string
    {
        $configured = config('velora.models.user');

        if (is_string($configured) && class_exists($configured)) {
            return $configured;
        }

        return User::class;
    }

    public function instance(): Model
    {
        /** @var Model $user */
        $user = app($this->className());

        return $user;
    }

    public function query(): Builder
    {
        return $this->instance()->newQuery();
    }

    public function table(): string
    {
        return $this->instance()->getTable();
    }

    public function keyName(): string
    {
        return $this->instance()->getKeyName();
    }
}
