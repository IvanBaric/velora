<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeamDropdown extends Component
{
    #[Locked]
    public string $variant = 'dropdown';

    public function mount(string $variant = 'dropdown'): void
    {
        $this->variant = $variant;
    }

    public function render(): View
    {
        return view('velora::livewire.team-dropdown', [
            'activeTeam' => team(),
            // memberships() is scoped to the current team; dropdown must show all teams the user belongs to.
            'allTeams' => auth()->user()?->memberships()
                ->withoutGlobalScopes()
                ->where('status', TeamMembershipStatus::Active->value)
                ->with('team')
                ->get()
                ->pluck('team')
                ->filter()
                ->values() ?? collect(),
            'variant' => $this->variant,
        ]);
    }
}
