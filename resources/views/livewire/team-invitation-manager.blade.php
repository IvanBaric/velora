<div class="space-y-4">
    <div>
        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Invitation activity</div>
        <div class="mt-1 text-sm text-zinc-500">Track pending, expired and accepted invitations in one list.</div>
    </div>

    @foreach ($invitations as $invitation)
        <div class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $invitation->email }}</div>
                    <div class="mt-1 text-xs text-zinc-500">{{ $roleLabels[$invitation->role_slug] ?? $invitation->role_slug }}</div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:badge color="{{ $invitation->status->color() }}">{{ $invitation->status->value }}</flux:badge>

                    @if ($invitation->status !== \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted)
                        <flux:button wire:click="resendInvitation('{{ $invitation->uuid }}')" variant="ghost" size="sm">Resend</flux:button>
                    @endif

                    @if (in_array($invitation->status, [\IvanBaric\Velora\Enums\TeamInvitationStatus::Pending, \IvanBaric\Velora\Enums\TeamInvitationStatus::Expired], true))
                        <flux:button wire:click="revokeInvitation('{{ $invitation->uuid }}')" variant="danger" size="sm">Revoke</flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="pt-2">
        {{ $invitations->links() }}
    </div>
</div>
