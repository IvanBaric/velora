<div class="space-y-4">
    @foreach ($memberships as $membership)
        <div class="group rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $membership->user->name }}</div>
                        @if ($membership->is_owner)
                            <flux:badge color="amber">Owner</flux:badge>
                        @else
                            <flux:badge color="emerald">Member</flux:badge>
                        @endif
                    </div>

                    <div class="mt-1 text-sm text-zinc-500">{{ $membership->user->email }}</div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $membership->roles->pluck('name')->implode(', ') ?: ($membership->is_owner ? 'Full workspace access' : 'No role') }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button wire:click="openMembershipDetails('{{ $membership->uuid }}')" variant="ghost" size="sm">Details</flux:button>
                    @if (! $membership->is_owner)
                        <flux:button wire:click="requestRoleChange('{{ $membership->uuid }}')" variant="ghost" size="sm">Change role</flux:button>
                        <flux:button wire:click="requestRemoveMember('{{ $membership->uuid }}')" variant="danger" size="sm">Remove</flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="pt-2">
        {{ $memberships->links() }}
    </div>

    <flux:modal wire:model="showMembershipDetailsModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Membership details</flux:heading>
            <flux:subheading class="mt-1">Inspect the member profile, status and invitation metadata.</flux:subheading>
        </div>

        @if ($membershipDetails)
            <div class="space-y-3 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-lg border p-3">
                        <div class="text-xs text-zinc-500">Member</div>
                        <div class="font-medium">{{ data_get($membershipDetails, 'user.name') }}</div>
                        <div class="text-xs text-zinc-500">{{ data_get($membershipDetails, 'user.email') }}</div>
                    </div>
                    <div class="rounded-lg border p-3">
                        <div class="text-xs text-zinc-500">Membership</div>
                        <div><span class="text-zinc-500">UUID:</span> {{ data_get($membershipDetails, 'uuid') }}</div>
                        <div><span class="text-zinc-500">Status:</span> {{ data_get($membershipDetails, 'status') }}</div>
                        <div><span class="text-zinc-500">Owner:</span> {{ data_get($membershipDetails, 'is_owner') ? 'Yes' : 'No' }}</div>
                    </div>
                </div>

                <div class="rounded-lg border p-3 space-y-1">
                    <div><span class="text-zinc-500">Roles:</span>
                        {{ collect(data_get($membershipDetails, 'roles', []))->filter()->implode(', ') ?: 'No role' }}
                    </div>
                    <div><span class="text-zinc-500">Joined at:</span> {{ data_get($membershipDetails, 'joined_at') ?? '-' }}</div>
                    <div><span class="text-zinc-500">Last seen:</span> {{ data_get($membershipDetails, 'last_seen_at') ?? '-' }}</div>
                </div>

                @if (data_get($membershipDetails, 'invited_email') || data_get($membershipDetails, 'invited_by_name') || data_get($membershipDetails, 'invited_by_email'))
                    <div class="rounded-lg border p-3 space-y-1">
                        <div class="text-xs text-zinc-500">Invitation</div>
                        @if (data_get($membershipDetails, 'invited_email'))
                            <div><span class="text-zinc-500">Invited email:</span> {{ data_get($membershipDetails, 'invited_email') }}</div>
                        @endif
                        @if (data_get($membershipDetails, 'invited_by_name') || data_get($membershipDetails, 'invited_by_email'))
                            <div><span class="text-zinc-500">Invited by:</span>
                                {{ data_get($membershipDetails, 'invited_by_name') ?: data_get($membershipDetails, 'invited_by_email') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <flux:text>Loading...</flux:text>
        @endif

        <div class="flex justify-end">
            <flux:button wire:click="closeMembershipDetails" variant="ghost">Close</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRoleChangeModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Change role</flux:heading>
            <flux:subheading class="mt-1">Choose a new role assignment for this team member.</flux:subheading>
        </div>
        <flux:select wire:model="pendingRole" label="Role" variant="listbox">
            @foreach ($availableRoles as $slug => $roleName)
                <flux:select.option value="{{ $slug }}">{{ $roleName }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRoleChange" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="confirmRoleChange" variant="primary">Save</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRemoveMemberModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Remove member</flux:heading>
            <flux:subheading class="mt-1">This removes workspace access for the selected user.</flux:subheading>
        </div>
        <flux:text>Remove {{ $pendingRemoveUserName }} from this team?</flux:text>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRemoveMember" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="confirmRemoveMember" variant="danger">Remove</flux:button>
        </div>
    </flux:modal>
</div>
