<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Actions\LeaveTeamAction;
use IvanBaric\Velora\Actions\UpdateTeamNameAction;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;
use IvanBaric\Velora\Support\TeamPlanUsage;
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

    public bool $canCreateTeam = false;

    public bool $canUpdateTeam = false;

    protected $listeners = [
        'close-invitations-modal' => 'closeInvitationsModal',
        'close-create-team-modal' => 'closeCreateTeamModal',
        'member-search-cleared' => 'clearSearch',
    ];

    public function mount(): void
    {
        $this->name = (string) team()->name;
        $this->canCreateTeam = false;
        $this->canUpdateTeam = $this->userHasPermission(TeamPermissions::TEAMS_UPDATE);
    }

    public function updatedSearch(): void
    {
        $this->dispatch('search-updated', search: $this->search);
    }

    public function closeSearchModal(): void
    {
        $this->showSearchModal = false;
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->dispatch('search-updated', search: '');
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
        abort(403);
    }

    public function openBasicInfoModal(): void
    {
        abort_unless($this->userHasPermission(TeamPermissions::TEAMS_UPDATE), 403);

        $this->showBasicInfoModal = true;
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
        abort_unless($this->userHasPermission(TeamPermissions::TEAMS_UPDATE), 403);
        Gate::authorize('update', team());

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $updateTeamName->execute(team(), $this->name);
        $this->showBasicInfoModal = false;
        $this->toastFromResult($result);
    }

    public function createTeam(): void
    {
        abort(403);
    }

    public function render(): View
    {
        $invitationBlockedMessage = $this->invitationBlockedMessage();

        return view('velora::livewire.team-settings', [
            'team' => team(),
            'canInviteWithinCurrentPlan' => $invitationBlockedMessage === null,
            'invitationBlockedMessage' => $invitationBlockedMessage,
        ])->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }

    protected function userHasPermission(string $permissionCode): bool
    {
        return (bool) auth()->user()?->hasPermission($permissionCode, (int) team()->getKey());
    }

    protected function invitationBlockedMessage(): ?string
    {
        try {
            app(PlanAccess::class)->assertWithinLimit(
                team(),
                PlanFeatures::TEAM_MEMBERS_LIMIT,
                TeamPlanUsage::members(team()),
            );

            return null;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException) {
            $planCode = (string) (team()->plan_code ?: 'starter');
            $planName = __("plans::plans.{$planCode}.name");

            return __('Invitations are not available on the :plan plan because the team member limit has been reached. Upgrade your plan to add collaborators.', [
                'plan' => $planName,
            ]);
        }
    }
}
