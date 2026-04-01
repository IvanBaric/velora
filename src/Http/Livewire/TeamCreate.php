<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use IvanBaric\Velora\Actions\CreateTeamAction;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\ActionResult;
use Livewire\Component;

class TeamCreate extends Component
{
    use InteractsWithActionResults;

    public string $name = '';

    public bool $modal = false;

    public function mount(bool $modal = false): void
    {
        $this->modal = $modal;
    }

    public function createTeam(CreateTeamAction $createTeam)
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var Team $team */
        $team = $createTeam->execute(auth()->user(), $this->name);

        set_current_team($team);
        $this->toastFromResult(ActionResult::success("Team {$team->name} created."));

        return $this->redirectRoute('teams.settings');
    }

    public function render(): View
    {
        $view = view('velora::livewire.team-create');

        if ($this->modal) {
            return $view;
        }

        return $view->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }
}
