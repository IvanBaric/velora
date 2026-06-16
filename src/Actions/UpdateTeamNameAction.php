<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\TeamPermissions;

final class UpdateTeamNameAction
{
    use AuthorizesActions;

    public function execute(Model $team, string $name): ActionResult
    {
        if ($result = $this->authorizeAction(TeamPermissions::TEAMS_UPDATE, $team)) {
            return ActionResult::fromCorexis($result);
        }

        $team->update(['name' => $name]);

        return ActionResult::success(__('Naziv tima je ažuriran.'));
    }
}
