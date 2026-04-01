<div class="space-y-4">
    <div class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Member</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($memberships as $membership)
                    <flux:table.row>
                        <flux:table.cell variant="strong">{{ $membership->user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $membership->user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:tooltip :content="$membership->status?->tooltip() ?? ''" position="bottom">
                                <div class="inline-flex items-center gap-2">
                                    @if ($membership->is_owner)
                                        <flux:badge color="amber" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="key" class="size-4" />
                                                Owner
                                            </span>
                                        </flux:badge>
                                    @else
                                        <flux:badge color="{{ $membership->status?->color() ?? 'zinc' }}" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="{{ $membership->status?->icon() ?? 'minus-circle' }}" class="size-4" />
                                                {{ ucfirst((string) ($membership->status?->value ?? 'unknown')) }}
                                            </span>
                                        </flux:badge>
                                    @endif
                                </div>
                            </flux:tooltip>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $membership->roles->first()?->name ?: '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon:trailing="ellipsis-horizontal"
                                        aria-label="Member actions"
                                    />

                                    <flux:menu>
                                        <flux:menu.item icon="information-circle" wire:click="openMembershipDetails('{{ $membership->uuid }}')">
                                            Details
                                        </flux:menu.item>

                                        @if (! $membership->is_owner)
                                            <flux:menu.item icon="shield-check" wire:click="requestRoleChange('{{ $membership->uuid }}')">
                                                Change role
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="requestRemoveMember('{{ $membership->uuid }}')">
                                                Remove
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="pt-2">
        {{ $memberships->links() }}
    </div>

    <flux:modal wire:model="showMembershipDetailsModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Membership details</flux:heading>
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
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-zinc-500">Status:</span>
                            @php
                                $status = \IvanBaric\Velora\Enums\TeamMembershipStatus::tryFrom((string) data_get($membershipDetails, 'status'));
                            @endphp
                            @if ($status)
                                <flux:tooltip :content="$status->tooltip()" position="bottom">
                                    <flux:badge color="{{ $status->color() }}" size="sm" inset="top bottom">
                                        <span class="inline-flex items-center gap-1.5">
                                            <flux:icon name="{{ $status->icon() }}" class="size-4" />
                                            {{ ucfirst($status->value) }}
                                        </span>
                                    </flux:badge>
                                </flux:tooltip>
                            @else
                                <span>{{ data_get($membershipDetails, 'status') }}</span>
                            @endif
                        </div>
                        <div><span class="text-zinc-500">Owner:</span> {{ data_get($membershipDetails, 'is_owner') ? 'Yes' : 'No' }}</div>
                    </div>
                </div>

                <div class="rounded-lg border p-3 space-y-1">
                    <div><span class="text-zinc-500">Role:</span>
                        {{ data_get($membershipDetails, 'role') ?: 'No role' }}
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
        </div>
        <flux:text>Remove {{ $pendingRemoveUserName }} from this team?</flux:text>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRemoveMember" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="confirmRemoveMember" variant="danger">Remove</flux:button>
        </div>
    </flux:modal>
</div>
