<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Actions\RemoveTeamMemberAction;
use IvanBaric\Velora\Actions\SyncMembershipRoleAction;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Models\UserRole;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\PlanFeatures;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TeamMemberManager extends Component
{
    use InteractsWithActionResults;
    use WithPagination;

    public string $search = '';

    public bool $showRoleChangeModal = false;

    public bool $showRemoveMemberModal = false;

    public bool $showMembershipDetailsModal = false;

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $membershipDetails = null;

    #[Locked]
    public ?string $pendingRoleMembershipUuid = null;

    public ?string $pendingRole = null;

    #[Locked]
    public ?string $pendingRoleUserName = null;

    #[Locked]
    public ?string $pendingRemoveMembershipUuid = null;

    #[Locked]
    public ?string $pendingRemoveUserName = null;

    protected $listeners = ['search-updated' => 'handleSearchUpdated'];

    public function handleSearchUpdated(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->dispatch('member-search-cleared');
    }

    public function requestRoleChange(string $membershipUuid): void
    {
        Gate::authorize('manageMembers', team());

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $membership = $this->resolveMembershipByUuid($membershipUuid, ['user']);
        $this->loadMembershipRoles($membership);

        if (! $this->canCurrentUserChangeRole($membership)) {
            return;
        }

        $this->pendingRoleMembershipUuid = (string) $membership->uuid;
        $this->pendingRole = (string) ($membership->roles->first()?->slug ?? Role::getDefault(team()->getKey())?->slug ?? '');
        $this->pendingRoleUserName = $membership->user?->name;
        $this->showRoleChangeModal = true;
    }

    public function openMembershipDetails(string $membershipUuid): void
    {
        Gate::authorize('manageMembers', team());

        $membership = $this->resolveMembershipByUuid($membershipUuid, ['user', 'inviter']);
        $this->loadMembershipRoles($membership);

        $this->membershipDetails = [
            'uuid' => (string) $membership->uuid,
            'user' => [
                'name' => (string) ($membership->user?->name ?? ''),
                'email' => (string) ($membership->user?->email ?? ''),
            ],
            'status' => $membership->status?->value,
            'status_label' => $membership->status?->label(),
            'status_icon' => $membership->status?->icon(),
            'status_tooltip' => $membership->status?->tooltip(),
            'is_owner' => (bool) $membership->is_owner,
            'role' => $membership->roles->first()?->name,
            'joined_at' => $membership->joined_at?->format('d.m.Y. H:i'),
            'last_seen_at' => $membership->last_seen_at?->format('d.m.Y. H:i'),
            'invited_email' => $membership->invited_email,
            'invited_by_name' => $membership->inviter?->name,
            'invited_by_email' => $membership->inviter?->email,
        ];

        $this->showMembershipDetailsModal = true;
    }

    public function closeMembershipDetails(): void
    {
        $this->showMembershipDetailsModal = false;
        $this->membershipDetails = null;
    }

    public function confirmRoleChange(SyncMembershipRoleAction $syncMembershipRole): void
    {
        Gate::authorize('manageMembers', team());
        abort_unless($this->pendingRoleMembershipUuid && $this->pendingRole, 422);

        if (! $this->rolesAndPermissionsAvailable()) {
            return;
        }

        $membership = $this->resolveMembershipByUuid((string) $this->pendingRoleMembershipUuid);
        if (! $this->canCurrentUserChangeRole($membership)) {
            $this->cancelRoleChange();

            return;
        }

        $result = $syncMembershipRole->execute($membership, (string) $this->pendingRole);

        $this->cancelRoleChange();
        $this->toastFromResult($result);
    }

    public function cancelRoleChange(): void
    {
        $this->showRoleChangeModal = false;
        $this->pendingRoleMembershipUuid = null;
        $this->pendingRole = null;
        $this->pendingRoleUserName = null;
    }

    public function requestRemoveMember(string $membershipUuid): void
    {
        Gate::authorize('manageMembers', team());

        $membership = $this->resolveMembershipByUuid($membershipUuid, ['user']);
        if (! $this->canCurrentUserRemoveMember($membership)) {
            return;
        }

        $this->pendingRemoveMembershipUuid = (string) $membership->uuid;
        $this->pendingRemoveUserName = $membership->user?->name;
        $this->showRemoveMemberModal = true;
    }

    public function confirmRemoveMember(RemoveTeamMemberAction $removeTeamMember): void
    {
        Gate::authorize('manageMembers', team());
        abort_unless($this->pendingRemoveMembershipUuid, 422);

        $membership = $this->resolveMembershipByUuid((string) $this->pendingRemoveMembershipUuid, ['user']);
        if (! $this->canCurrentUserRemoveMember($membership)) {
            $this->cancelRemoveMember();

            return;
        }

        $result = $removeTeamMember->execute($membership, auth()->id());

        $this->cancelRemoveMember();
        $this->toastFromResult($result);
    }

    public function cancelRemoveMember(): void
    {
        $this->showRemoveMemberModal = false;
        $this->pendingRemoveMembershipUuid = null;
        $this->pendingRemoveUserName = null;
    }

    public function render(): View
    {
        $memberships = TeamMembership::query()
            ->with(['user', 'inviter'])
            ->where('team_id', team()->getKey())
            ->whereHas('user', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->paginate(10);

        $this->loadMembershipRolesForPage($memberships);

        return view('velora::livewire.team-member-manager', [
            'memberships' => $memberships,
            'availableRoles' => Role::query()
                ->availableToTeam(team()->getKey())
                ->assignable()
                ->notHidden()
                ->pluck('name', 'slug')
                ->toArray(),
            'rolesAndPermissionsAvailable' => $this->rolesAndPermissionsAvailable(toast: false),
            'currentUserOwnsTeam' => $this->currentUserOwnsTeam(),
            'currentUserId' => auth()->id(),
        ]);
    }

    protected function rolesAndPermissionsAvailable(bool $toast = true): bool
    {
        try {
            app(PlanAccess::class)->assertEnabled(team(), PlanFeatures::ROLES_AND_PERMISSIONS);

            return true;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException $exception) {
            if ($toast) {
                $this->toastFromResult(ActionResult::error(
                    trim($exception->getMessage().' '.__('Postojeće uloge ostaju aktivne, ali trenutačni plan ne dopušta promjenu uloga suradnika. Nadogradite plan za nastavak.'))
                ));
            }

            return false;
        }
    }

    /**
     * @param  array<int, string>  $relations
     */
    protected function resolveMembershipByUuid(string $membershipUuid, array $relations = []): TeamMembership
    {
        /** @var TeamMembership $membership */
        $membership = TeamMembership::query()
            ->with($relations)
            ->where('team_id', team()->getKey())
            ->where('uuid', $membershipUuid)
            ->firstOrFail();

        return $membership;
    }

    protected function loadMembershipRoles(TeamMembership $membership): void
    {
        $this->hydrateMembershipRoles(new EloquentCollection([$membership]));
    }

    protected function loadMembershipRolesForPage(LengthAwarePaginator $memberships): void
    {
        $this->hydrateMembershipRoles($memberships->getCollection());
    }

    protected function hydrateMembershipRoles(EloquentCollection $memberships): void
    {
        if ($memberships->isEmpty()) {
            return;
        }

        $rolesByUserId = UserRole::query()
            ->active()
            ->with('role')
            ->where('team_id', team()->getKey())
            ->whereIn('user_id', $memberships->pluck('user_id')->unique()->all())
            ->get()
            ->groupBy('user_id')
            ->map(fn (EloquentCollection $assignments) => $assignments
                ->pluck('role')
                ->filter()
                ->sortBy('sort_order')
                ->take(1)
                ->values());

        $memberships->each(function (TeamMembership $membership) use ($rolesByUserId): void {
            $membership->setRelation(
                'roles',
                $rolesByUserId->get($membership->user_id, new EloquentCollection)
            );
        });
    }

    protected function canCurrentUserChangeRole(TeamMembership $membership): bool
    {
        return $this->currentUserOwnsTeam()
            && ! $membership->is_owner
            && (int) $membership->user_id !== (int) auth()->id();
    }

    protected function canCurrentUserRemoveMember(TeamMembership $membership): bool
    {
        return $this->currentUserOwnsTeam()
            && ! $membership->is_owner
            && (int) $membership->user_id !== (int) auth()->id();
    }

    protected function currentUserOwnsTeam(): bool
    {
        $userId = auth()->id();

        if (! $userId) {
            return false;
        }

        return TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', team()->getKey())
            ->where('user_id', (int) $userId)
            ->where('is_owner', true)
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();
    }
}
