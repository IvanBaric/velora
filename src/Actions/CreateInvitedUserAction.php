<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CreateInvitedUserAction
{
    /**
     * @param  array{name:string,password:string,email:string}  $attributes
     */
    public function execute(array $attributes): Model
    {
        /** @var Model $user */
        $user = DB::transaction(fn (): Model => velora_user_query()->create($attributes));

        return $user;
    }
}
