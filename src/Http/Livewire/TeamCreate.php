<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Flux\Flux;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;
use Livewire\Component;

class TeamCreate extends Component
{
    public string $name = '';

    public function createTeam()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::query()->create([
            'name' => $this->name,
        ]);

        $membership = TeamMembership::ensureForUser(auth()->user(), $team, true);
        $membership->assignRole('admin', $team);

        set_current_team($team);
        Flux::toast(variant: 'success', text: "Team {$team->name} created.");

        return $this->redirectRoute('teams.settings');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('velora::livewire.team-create')
            ->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }
}
