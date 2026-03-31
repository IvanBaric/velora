<div class="mx-auto max-w-7xl space-y-8 p-6">
    <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="bg-gradient-to-br from-white via-zinc-50 to-emerald-50/70 p-8 dark:from-zinc-950 dark:via-zinc-950 dark:to-emerald-950/20">
            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Team workspace
                    </div>

                    <div class="space-y-3">
                        <flux:heading size="xl">{{ $team->name }}</flux:heading>
                        <flux:subheading class="max-w-2xl">
                            Manage members, invitations, roles and team identity from one place.
                        </flux:subheading>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button wire:click="$set('showInvitationsModal', true)" variant="primary">Invitations</flux:button>
                        <flux:button wire:click="$dispatch('open-role-manager')" variant="ghost">Roles</flux:button>
                        <flux:button wire:click="$set('showBasicInfoModal', true)" variant="ghost">Settings</flux:button>
                        <flux:button wire:click="$set('showSearchModal', true)" variant="ghost">Search members</flux:button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80">
                        <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Members</div>
                        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Browse the active team roster and inspect role assignments.</div>
                    </div>
                    <div class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80">
                        <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Roles</div>
                        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Open the role manager to shape access and permissions.</div>
                    </div>
                    <div class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80">
                        <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Identity</div>
                        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Update the team name and general workspace details.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
        <div class="space-y-4">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">Team members</div>
                    <div class="mt-1 text-sm text-zinc-500">Roles, ownership and membership actions stay visible in one stream.</div>
                </div>
            </div>

            @livewire('teams.team-member-manager')
        </div>

        <aside class="space-y-4">
            <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Quick actions</div>
                <div class="mt-1 text-sm text-zinc-500">Shortcuts for the most common team admin tasks.</div>

                <div class="mt-5 grid gap-2">
                    <flux:button wire:click="$set('showInvitationsModal', true)" variant="ghost">Open invitations</flux:button>
                    <flux:button wire:click="$dispatch('open-role-manager')" variant="ghost">Manage roles</flux:button>
                    <flux:button wire:click="$set('showBasicInfoModal', true)" variant="ghost">Edit team details</flux:button>
                    <flux:button wire:click="$set('showSearchModal', true)" variant="ghost">Find a member</flux:button>
                </div>
            </div>

            @if (! auth()->user()?->membershipForCurrentTeam()?->is_owner)
                <div class="rounded-[1.75rem] border border-red-200 bg-red-50/80 p-5 shadow-xs dark:border-red-950 dark:bg-red-950/20">
                    <div class="text-sm font-semibold text-red-900 dark:text-red-200">Leave team</div>
                    <div class="mt-1 text-sm text-red-700 dark:text-red-300">You can leave this workspace at any time. Owners cannot leave from here.</div>

                    <div class="mt-4">
                        <flux:button wire:click="$set('showLeaveTeamModal', true)" variant="danger">Leave {{ $team->name }}</flux:button>
                    </div>
                </div>
            @endif
        </aside>
    </section>

    @livewire('roles.role-manager')

    <flux:modal wire:model="showSearchModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Search members</flux:heading>
            <flux:subheading class="mt-1">Filter the roster by name or email.</flux:subheading>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" label="Search" placeholder="Type a member name or email..." clearable />
    </flux:modal>

    <flux:modal wire:model="showInvitationsModal" flyout variant="floating" class="space-y-8">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Invitations</flux:heading>
                <flux:subheading class="mt-1">Invite new people and keep track of pending access requests.</flux:subheading>
            </div>

            <div class="space-y-8">
                @livewire('teams.team-invitation-form')
                @livewire('teams.team-invitation-manager')
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showBasicInfoModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Team settings</flux:heading>
            <flux:subheading class="mt-1">Update how this workspace is identified across the app.</flux:subheading>
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
            <flux:subheading class="mt-1">You will lose access to {{ $team->name }} until someone invites you again.</flux:subheading>
        </div>

        <flux:text>Leave {{ $team->name }}?</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showLeaveTeamModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="confirmLeaveTeam" variant="danger">Leave</flux:button>
        </div>
    </flux:modal>
</div>
