<div class="space-y-4">
    <div class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($invitations as $invitation)
                    <flux:table.row>
                        <flux:table.cell variant="strong">{{ $invitation->email }}</flux:table.cell>
                        <flux:table.cell>{{ $roleLabels[$invitation->role_slug] ?? $invitation->role_slug }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:tooltip :content="$invitation->status->tooltip()" position="bottom">
                                <flux:badge color="{{ $invitation->status->color() }}" size="sm" inset="top bottom">
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon name="{{ $invitation->status->icon() }}" class="size-4" />
                                        {{ ucfirst($invitation->status->value) }}
                                    </span>
                                </flux:badge>
                            </flux:tooltip>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon:trailing="ellipsis-horizontal"
                                        aria-label="Invitation actions"
                                    />

                                    <flux:menu>
                                        @if ($invitation->status !== \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted)
                                            <flux:menu.item icon="paper-airplane" wire:click="resendInvitation('{{ $invitation->uuid }}')">
                                                Resend
                                            </flux:menu.item>
                                        @endif

                                        @if (in_array($invitation->status, [\IvanBaric\Velora\Enums\TeamInvitationStatus::Pending, \IvanBaric\Velora\Enums\TeamInvitationStatus::Expired], true))
                                            <flux:menu.item icon="trash" variant="danger" wire:click="revokeInvitation('{{ $invitation->uuid }}')">
                                                Revoke
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
        {{ $invitations->links() }}
    </div>
</div>
