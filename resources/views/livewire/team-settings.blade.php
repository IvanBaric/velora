<div class="mx-auto max-w-5xl space-y-10 p-8">
    <section class="px-1 py-2">
        <div class="flex flex-col gap-8 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-2">
                <flux:heading size="xl">{{ $team->name }}</flux:heading>
            </div>

            <flux:dropdown position="bottom" align="end">
                <flux:button variant="filled" icon:trailing="ellipsis-vertical" aria-label="Team actions" />

                <flux:menu>
                    <flux:menu.item icon="envelope" wire:click="$set('showInvitationsModal', true)">
                        Invitations
                    </flux:menu.item>
                    <flux:menu.item icon="plus" wire:click="openCreateTeamModal">
                        Create team
                    </flux:menu.item>
                    <flux:menu.item icon="shield-check" wire:click="$dispatch('open-role-manager')">
                        Roles
                    </flux:menu.item>
                    <flux:menu.item icon="cog-6-tooth" wire:click="$set('showBasicInfoModal', true)">
                        Settings
                    </flux:menu.item>
                    <flux:menu.item icon="magnifying-glass" wire:click="$set('showSearchModal', true)">
                        Search
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </section>

    <section class="space-y-6">
        <div class="space-y-5">
            <div class="text-sm font-medium uppercase tracking-[0.18em] text-zinc-400">Members</div>

            @livewire('teams.team-member-manager')
        </div>

        @if (! auth()->user()?->membershipForCurrentTeam()?->is_owner)
            <div class="rounded-[1.75rem] border border-red-200 bg-red-50/80 p-5 shadow-xs dark:border-red-950 dark:bg-red-950/20">
                <div class="text-sm font-semibold text-red-900 dark:text-red-200">Leave team</div>
                <div class="mt-4">
                    <flux:button wire:click="$set('showLeaveTeamModal', true)" variant="danger">Leave {{ $team->name }}</flux:button>
                </div>
            </div>
        @endif
    </section>

    @livewire('roles.role-manager')

    <flux:modal wire:model="showSearchModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Search members</flux:heading>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" label="Search" placeholder="Type a member name or email..." clearable />
    </flux:modal>

    <flux:modal wire:model="showInvitationsModal" flyout variant="floating" class="space-y-8">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Invitations</flux:heading>
            </div>

            <div class="space-y-8">
                @livewire('teams.team-invitation-form')
                @livewire('teams.team-invitation-manager')
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCreateTeamModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Create team</flux:heading>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input wire:model="createTeamName" label="Team name" placeholder="Enter team name..." clearable />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeCreateTeamModal" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create team</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showBasicInfoModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Team settings</flux:heading>
        </div>

        <form wire:submit="updateTeamName" class="space-y-6">
            <flux:input wire:model="name" label="Team name" placeholder="Enter team name..." clearable />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showLeaveTeamModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Leave team</flux:heading>
        </div>

        <flux:text>Leave {{ $team->name }}?</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showLeaveTeamModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="confirmLeaveTeam" variant="danger">Leave</flux:button>
        </div>
    </flux:modal>
</div>
