<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\Team;

final class TeamSwitchController
{
    public function __invoke(Team $team): RedirectResponse
    {
        abort_unless(
            auth()->user()?->memberships()
                ->withoutGlobalScopes()
                ->where('team_id', $team->getKey())
                ->where('status', TeamMembershipStatus::Active->value)
                ->exists(),
            403
        );

        set_current_team($team);

        return redirect()->back();
    }
}
