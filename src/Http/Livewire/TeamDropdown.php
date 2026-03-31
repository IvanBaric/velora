<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Livewire\Component;

class TeamDropdown extends Component
{
    public string $variant = 'dropdown';

    public function mount(string $variant = 'dropdown'): void
    {
        $this->variant = $variant;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('velora::livewire.team-dropdown', [
            'currentTeam' => team(),
            // memberships() is scoped to the current team; dropdown must show all teams the user belongs to.
            'allTeams' => auth()->user()?->memberships()->withoutGlobalScopes()->with('team')->get()
                ->pluck('team')
                ->filter()
                ->values() ?? collect(),
            'variant' => $this->variant,
        ]);
    }
}
