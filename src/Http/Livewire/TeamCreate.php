<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use IvanBaric\Velora\Actions\CreateTeamAction;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\UserRole;
use IvanBaric\Velora\Support\ActionResult;
use Livewire\Component;

class TeamCreate extends Component
{
    use InteractsWithActionResults;

    public string $name = '';

    public bool $modal = false;

    public function mount(bool $modal = false): void
    {
        abort_unless($this->userHasAssignedPermission('teams.create'), 403);

        $this->modal = $modal;
    }

    public function createTeam(CreateTeamAction $createTeam)
    {
        abort_unless($this->userHasAssignedPermission('teams.create'), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var Team $team */
        $team = $createTeam->execute(auth()->user(), $this->name);

        set_current_team($team);
        $this->toastFromResult(ActionResult::success("Tim {$team->name} je kreiran."));

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

    protected function userHasAssignedPermission(string $permissionCode): bool
    {
        $userId = auth()->id();

        if (! $userId) {
            return false;
        }

        return UserRole::query()
            ->active()
            ->where('user_id', $userId)
            ->where('team_id', team()->getKey())
            ->whereHas('role', function ($query) use ($permissionCode): void {
                $query->withoutGlobalScopes()
                    ->whereHas('permissionItems', fn ($permissionQuery) => $permissionQuery
                        ->where('code', $permissionCode)
                        ->where('is_active', true));
            })
            ->exists();
    }
}
