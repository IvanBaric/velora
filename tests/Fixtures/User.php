<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
