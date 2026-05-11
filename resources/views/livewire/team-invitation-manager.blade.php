<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
    <div class="hidden grid-cols-12 gap-3 bg-zinc-50/40 px-6 py-3 text-[11px] font-medium uppercase tracking-[0.16em] text-zinc-400 dark:bg-zinc-900/30 dark:text-zinc-500 sm:grid">
        <div class="col-span-5">{{ __('Email') }}</div>
        <div class="col-span-3">{{ __('Uloga') }}</div>
        <div class="col-span-3">{{ __('Status') }}</div>
        <div class="col-span-1 text-right">{{ __('Akcije') }}</div>
    </div>

    @forelse ($invitations as $invitation)
        <div wire:key="invitation-{{ $invitation->uuid }}" class="group/row grid grid-cols-1 items-center gap-3 border-t border-zinc-100/70 px-4 py-4 transition duration-150 ease-out first:border-t-0 hover:bg-zinc-50/50 focus-within:bg-zinc-50/60 dark:border-zinc-800/70 dark:hover:bg-zinc-900/60 dark:focus-within:bg-zinc-900/70 sm:grid-cols-12 sm:px-6">
            <div class="col-span-5 min-w-0">
                <div class="truncate text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">{{ $invitation->email }}</div>
                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $invitation->last_sent_at ? __('Poslano :date', ['date' => $invitation->last_sent_at->format('d.m.Y. H:i')]) : __('Još nije poslano') }}
                </div>
            </div>

            <div class="col-span-3 text-sm text-zinc-700 dark:text-zinc-200">
                {{ $roleLabels[$invitation->role_slug] ?? $invitation->role_slug }}
            </div>

            <div class="col-span-3">
                <flux:tooltip :content="$invitation->status->tooltip()" position="bottom">
                    <flux:badge color="{{ $invitation->status->color() }}" size="sm" inset="top bottom" icon="{{ $invitation->status->icon() }}">
                        {{ ucfirst($invitation->status->value) }}
                    </flux:badge>
                </flux:tooltip>
            </div>

            <div class="col-span-1 flex justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:tooltip :content="__('Akcije pozivnice')">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Akcije pozivnice') }}" />
                    </flux:tooltip>

                    <flux:menu>
                        @if ($invitation->status !== \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted)
                            <flux:menu.item icon="paper-airplane" wire:click="resendInvitation('{{ $invitation->uuid }}')">
                                {{ __('Pošalji ponovno') }}
                            </flux:menu.item>
                        @endif

                        @if (in_array($invitation->status, [\IvanBaric\Velora\Enums\TeamInvitationStatus::Pending, \IvanBaric\Velora\Enums\TeamInvitationStatus::Expired], true))
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="revokeInvitation('{{ $invitation->uuid }}')">
                                {{ __('Opozovi') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    @empty
        <div class="p-6 sm:p-10">
            <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-50 text-zinc-400 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-white/10">
                <flux:icon icon="inbox" variant="micro" class="size-6" />
            </div>
            <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Nema pozivnica') }}</p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Poslane pozivnice prikazat će se ovdje.') }}</p>
        </div>
    @endforelse

    @if ($invitations->hasPages())
        <div class="border-t border-zinc-100/70 px-4 py-3 dark:border-zinc-800/70">
            {{ $invitations->links() }}
        </div>
    @endif
</div>
