<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use IvanBaric\Velora\Traits\HasVelora;

final class User extends Authenticatable
{
    use HasVelora;

    protected $table = 'users';

    protected $guarded = [];
}
