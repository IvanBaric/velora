<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Actions\CreateTeamAction;
use IvanBaric\Velora\Actions\LeaveTeamAction;
use IvanBaric\Velora\Actions\UpdateTeamNameAction;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Team;
use Livewire\Component;

class TeamSettings extends Component
{
    use InteractsWithActionResults;

    public string $name = '';

    public bool $showLeaveTeamModal = false;

    public bool $showInvitationsModal = false;

    public bool $showBasicInfoModal = false;

    public bool $showCreateTeamModal = false;

    public bool $showSearchModal = false;

    public string $search = '';

    public string $createTeamName = '';

    protected $listeners = [
        'close-invitations-modal' => 'closeInvitationsModal',
        'close-create-team-modal' => 'closeCreateTeamModal',
    ];

    public function mount(): void
    {
        $this->name = (string) team()->name;
    }

    public function updatedSearch(): void
    {
        $this->dispatch('search-updated', search: $this->search);
    }

    public function closeInvitationsModal(): void
    {
        $this->showInvitationsModal = false;
    }

    public function closeCreateTeamModal(): void
    {
        $this->showCreateTeamModal = false;
        $this->createTeamName = '';
        $this->resetErrorBag('createTeamName');
    }

    public function openCreateTeamModal(): void
    {
        $this->createTeamName = '';
        $this->resetErrorBag('createTeamName');
        $this->showCreateTeamModal = true;
    }

    public function confirmLeaveTeam(LeaveTeamAction $leaveTeam): void
    {
        $membership = auth()->user()?->membershipForCurrentTeam();
        if (! $membership || $membership->is_owner) {
            $this->showLeaveTeamModal = false;

            return;
        }

        $result = $leaveTeam->execute($membership, (string) auth()->user()->email, auth()->id());
        $this->toastFromResult($result);
        $this->showLeaveTeamModal = false;

        if (! $result->success) {
            return;
        }

        $this->redirectRoute('dashboard');
    }

    public function updateTeamName(UpdateTeamNameAction $updateTeamName): void
    {
        Gate::authorize('update', team());

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $updateTeamName->execute(team(), $this->name);
        $this->showBasicInfoModal = false;
        $this->toastFromResult($result);
    }

    public function createTeam(CreateTeamAction $createTeam)
    {
        $this->validate([
            'createTeamName' => ['required', 'string', 'max:255'],
        ]);

        /** @var Team $team */
        $team = $createTeam->execute(auth()->user(), $this->createTeamName);

        set_current_team($team);
        $this->showCreateTeamModal = false;
        $this->createTeamName = '';
        session()->flash('status', "Team {$team->name} created.");
        session()->flash('status_variant', 'success');

        return $this->redirectRoute('teams.settings');
    }

    public function render(): View
    {
        return view('velora::livewire.team-settings', [
            'team' => team(),
        ])->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }
}
