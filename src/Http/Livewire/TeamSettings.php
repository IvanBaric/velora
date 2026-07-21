<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use IvanBaric\Velora\Actions\LeaveTeamAction;
use IvanBaric\Velora\Actions\UpdateTeamNameAction;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPermissions;
use IvanBaric\Velora\Support\TeamPlanUsage;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeamSettings extends Component
{
    use InteractsWithActionResults;

    public string $name = '';

    public bool $showLeaveTeamModal = false;

    public string $leaveTeamPassword = '';

    public ?string $leaveTeamUnavailableMessage = null;

    public bool $showInvitationsModal = false;

    public bool $showBasicInfoModal = false;

    public bool $showCreateTeamModal = false;

    public bool $showSearchModal = false;

    public string $search = '';

    public string $createTeamName = '';

    #[Locked]
    public bool $canCreateTeam = false;

    #[Locked]
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

    public function openLeaveTeamModal(): void
    {
        $membership = auth()->user()?->membershipForCurrentTeam();

        $this->leaveTeamPassword = '';
        $this->resetErrorBag('leaveTeamPassword');
        $this->leaveTeamUnavailableMessage = $this->leaveTeamUnavailableMessage($membership);
        $this->showLeaveTeamModal = true;
    }

    public function closeLeaveTeamModal(): void
    {
        $this->showLeaveTeamModal = false;
        $this->leaveTeamPassword = '';
        $this->leaveTeamUnavailableMessage = null;
        $this->resetErrorBag('leaveTeamPassword');
    }

    public function confirmLeaveTeam(LeaveTeamAction $leaveTeam): void
    {
        $membership = auth()->user()?->membershipForCurrentTeam();
        $this->leaveTeamUnavailableMessage = $this->leaveTeamUnavailableMessage($membership);

        if ($this->leaveTeamUnavailableMessage !== null) {
            return;
        }

        $this->validate([
            'leaveTeamPassword' => ['required', 'string'],
        ], [
            'leaveTeamPassword.required' => __('Obavezno polje'),
            'leaveTeamPassword.string' => __('Lozinka mora biti tekst.'),
        ], [
            'leaveTeamPassword' => __('lozinka'),
        ]);

        if (! Hash::check($this->leaveTeamPassword, (string) auth()->user()?->password)) {
            $this->addError('leaveTeamPassword', __('Lozinka nije točna.'));

            return;
        }

        $result = $leaveTeam->execute($membership, (string) auth()->user()->email, auth()->id());
        $this->toastFromResult($result);
        $this->closeLeaveTeamModal();

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
            'canLeaveTeam' => $this->canLeaveCurrentTeam(),
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
                TeamPlanUsage::occupiedMemberSeats(team()),
            );

            return null;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException) {
            $planCode = (string) ((team()->getAttributes()['plan_code'] ?? null) ?: 'starter');
            $planName = __("plans::plans.{$planCode}.name");

            return __('Pozivnice nisu dostupne na planu :plan jer je dosegnut limit suradnika. Nadogradite plan kako biste dodali nove suradnike.', [
                'plan' => $planName,
            ]);
        }
    }

    protected function canLeaveCurrentTeam(): bool
    {
        return $this->leaveTeamUnavailableMessage(auth()->user()?->membershipForCurrentTeam()) === null;
    }

    protected function leaveTeamUnavailableMessage(mixed $membership): ?string
    {
        if ((bool) auth()->user()?->is_superadmin) {
            return __('Tehnička podrška ne može napustiti organizaciju.');
        }

        if (! $membership instanceof TeamMembership) {
            return __('Nemate aktivno članstvo u ovoj organizaciji.');
        }

        if ($this->activeMemberCount((int) $membership->team_id) <= 1) {
            return __('Organizaciju nije moguće napustiti jer ste posljednji aktivni suradnik.');
        }

        if ($membership->isOwner()) {
            return __('Vlasnik organizacije ne može napustiti organizaciju. Prvo prenesite vlasništvo drugom suradniku.');
        }

        return null;
    }

    protected function activeMemberCount(int $teamId): int
    {
        return TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', $teamId)
            ->where('status', TeamMembershipStatus::Active->value)
            ->count();
    }
}
