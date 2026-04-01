<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\ActionResult;

final class UpdateTeamNameAction
{
    public function execute(Team $team, string $name): ActionResult
    {
        $team->update(['name' => $name]);

        return ActionResult::success('Team name updated.');
    }
}
