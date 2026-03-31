<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Models\TeamInvitation;
use Livewire\Component;

class TeamSettings extends Component
{
    public string $name = '';

    public bool $showLeaveTeamModal = false;

    public bool $showInvitationsModal = false;

    public bool $showBasicInfoModal = false;

    public bool $showSearchModal = false;

    public string $search = '';

    protected $listeners = ['close-invitations-modal' => 'closeInvitationsModal'];

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

    public function confirmLeaveTeam(): void
    {
        $membership = auth()->user()?->membershipForCurrentTeam();
        if (! $membership || $membership->is_owner) {
            $this->showLeaveTeamModal = false;

            return;
        }

        TeamInvitation::query()
            ->active()
            ->where('email', TeamInvitation::normalizeEmail((string) auth()->user()->email))
            ->get()
            ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked(auth()->id(), ['reason' => 'member_left_team']));

        $membership->delete();

        $this->redirectRoute('dashboard');
    }

    public function updateTeamName(): void
    {
        Gate::authorize('update', team());

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        team()->update(['name' => $this->name]);
        $this->showBasicInfoModal = false;

        Flux::toast(variant: 'success', text: 'Team name updated.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('velora::livewire.team-settings', [
            'team' => team(),
        ])->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }
}
