<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Support\ActionResult;

final class UpdateTeamNameAction
{
    public function execute(Model $team, string $name): ActionResult
    {
        $team->update(['name' => $name]);

        return ActionResult::success(__('Naziv tima je ažuriran.'));
    }
}
