<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use IvanBaric\Velora\Enums\TeamMembershipStatus;

final class TeamSwitchController
{
    public function __invoke(int|string $team): RedirectResponse
    {
        $team = velora_team_query()
            ->whereKey($team)
            ->when(Schema::hasColumn(velora_team_table(), 'uuid'), fn ($query) => $query->orWhere('uuid', $team))
            ->firstOrFail();

        abort_unless(
            auth()->user()?->memberships()
                ->withoutGlobalScopes()
                ->where('team_id', $team->getKey())
                ->where('status', TeamMembershipStatus::Active->value)
                ->exists(),
            403
        );

        set_current_team($team);

        $redirectRoute = (string) config('velora.team_switch.redirect_route', 'teams.settings');
        $redirect = Route::has($redirectRoute)
            ? redirect()->route($redirectRoute)
            : redirect()->to('/');

        return $redirect->with('velora.toast', [
            'heading' => __('Tim promijenjen'),
            'text' => __('Aktivan tim je sada :team.', ['team' => $team->name]),
            'variant' => 'success',
        ]);
    }
}
