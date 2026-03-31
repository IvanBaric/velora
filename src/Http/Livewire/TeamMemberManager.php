<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Models\UserRole;
use Livewire\Component;
use Livewire\WithPagination;

class TeamMemberManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showRoleChangeModal = false;

    public bool $showRemoveMemberModal = false;

    public bool $showMembershipDetailsModal = false;

    /** @var array<string, mixed>|null */
    public ?array $membershipDetails = null;

    public ?string $pendingRoleMembershipUuid = null;

    public ?string $pendingRole = null;

    public ?string $pendingRoleUserName = null;

    public ?string $pendingRemoveMembershipUuid = null;

    public ?string $pendingRemoveUserName = null;

    protected $listeners = ['search-updated' => 'handleSearchUpdated'];

    public function handleSearchUpdated(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function requestRoleChange(string $membershipUuid): void
    {
        Gate::authorize('manageMembers', team());

        $membership = $this->resolveMembershipByUuid($membershipUuid, ['user']);
        $this->loadMembershipRoles($membership);

        if ($membership->is_owner) {
            return;
        }

        $this->pendingRoleMembershipUuid = (string) $membership->uuid;
        $this->pendingRole = (string) ($membership->roles->pluck('slug')->first() ?? Role::getDefault(team()->getKey())?->slug ?? '');
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
            'is_owner' => (bool) $membership->is_owner,
            'roles' => $membership->roles->pluck('name')->values()->all(),
            'joined_at' => $membership->joined_at?->toDateTimeString(),
            'last_seen_at' => $membership->last_seen_at?->toDateTimeString(),
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

    public function confirmRoleChange(): void
    {
        Gate::authorize('manageMembers', team());
        abort_unless($this->pendingRoleMembershipUuid && $this->pendingRole, 422);

        $membership = $this->resolveMembershipByUuid((string) $this->pendingRoleMembershipUuid);
        if ($membership->is_owner) {
            $this->cancelRoleChange();

            return;
        }

        $membership->syncRoles([$this->pendingRole]);

        $this->cancelRoleChange();
        Flux::toast(variant: 'success', text: 'Member role updated.');
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
        if ($membership->is_owner) {
            return;
        }

        $this->pendingRemoveMembershipUuid = (string) $membership->uuid;
        $this->pendingRemoveUserName = $membership->user?->name;
        $this->showRemoveMemberModal = true;
    }

    public function confirmRemoveMember(): void
    {
        Gate::authorize('manageMembers', team());
        abort_unless($this->pendingRemoveMembershipUuid, 422);

        $membership = $this->resolveMembershipByUuid((string) $this->pendingRemoveMembershipUuid, ['user']);
        if ($membership->is_owner) {
            $this->cancelRemoveMember();

            return;
        }

        $email = TeamInvitation::normalizeEmail((string) $membership->user?->email);

        TeamInvitation::query()
            ->active()
            ->where('email', $email)
            ->get()
            ->each(fn (TeamInvitation $invitation) => $invitation->markRevoked(auth()->id(), ['reason' => 'member_removed']));

        $membership->delete();

        $this->cancelRemoveMember();
        Flux::toast(variant: 'success', text: 'Member removed from team.');
    }

    public function cancelRemoveMember(): void
    {
        $this->showRemoveMemberModal = false;
        $this->pendingRemoveMembershipUuid = null;
        $this->pendingRemoveUserName = null;
    }

    public function render(): \Illuminate\Contracts\View\View
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
        ]);
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
                ->values());

        $memberships->each(function (TeamMembership $membership) use ($rolesByUserId): void {
            $membership->setRelation(
                'roles',
                $rolesByUserId->get($membership->user_id, new EloquentCollection())
            );
        });
    }
}
