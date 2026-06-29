<x-admin-ui::panel id="team-invitations-table" loading loading-target="resendInvitation" loading-text="{{ __('Šaljem pozivnicu...') }}">
    @if ($invitations->isNotEmpty())
        <div class="admin-list-header lg:grid-cols-[minmax(0,1fr)_9rem_9rem_5rem]">
            <span>{{ __('Email') }}</span>
            <span>{{ __('Uloga') }}</span>
            <span>{{ __('Status') }}</span>
            <span class="text-right">{{ __('Akcije') }}</span>
        </div>
    @endif

    @forelse ($invitations as $invitation)
        <article
            wire:key="invitation-{{ $invitation->uuid }}"
            wire:loading.class="opacity-70"
            wire:target="resendInvitation('{{ $invitation->uuid }}')"
            class="admin-list-row p-4 transition sm:p-6 lg:grid-cols-[minmax(0,1fr)_9rem_9rem_5rem]"
        >
            <div class="min-w-0">
                <h3 class="truncate text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">
                    {{ $invitation->email }}
                </h3>
                <p class="mt-0.5 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                    {{ $invitation->last_sent_at ? __('Poslano :date', ['date' => $invitation->last_sent_at->format('d.m.Y. H:i')]) : __('Još nije poslano') }}
                </p>
                <span
                    wire:loading.inline-flex
                    wire:target="resendInvitation('{{ $invitation->uuid }}')"
                    class="mt-2 hidden items-center gap-2 rounded-full bg-accent/10 px-2.5 py-1 text-xs font-medium text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25"
                >
                    <flux:icon.loading class="size-3.5" />
                    {{ __('Šaljem pozivnicu...') }}
                </span>
            </div>

            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <span class="me-2 text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500 lg:hidden">{{ __('Uloga') }}</span>
                {{ __($roleLabels[$invitation->role_slug] ?? $invitation->role_slug) }}
            </div>

            <div>
                <span class="me-2 text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500 lg:hidden">{{ __('Status') }}</span>
                <flux:tooltip :content="$invitation->status->tooltip()" position="bottom">
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] ring-1',
                        'bg-accent/10 text-accent-content ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25' => $invitation->status === \IvanBaric\Velora\Enums\TeamInvitationStatus::Pending || $invitation->status === \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted,
                        'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20' => $invitation->status === \IvanBaric\Velora\Enums\TeamInvitationStatus::Expired,
                        'bg-zinc-100 text-zinc-600 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10' => $invitation->status === \IvanBaric\Velora\Enums\TeamInvitationStatus::Revoked,
                    ])>
                        <flux:icon :icon="$invitation->status->icon()" class="size-3.5" />
                        {{ $invitation->status->label() }}
                    </span>
                </flux:tooltip>
            </div>

            <div class="flex justify-start lg:justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:tooltip :content="__('Otvori akcije za ovu pozivnicu')">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Akcije pozivnice') }}" />
                    </flux:tooltip>

                    <flux:menu>
                        @if ($invitation->status !== \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted)
                            <flux:menu.item
                                as="button"
                                type="button"
                                icon="paper-airplane"
                                wire:click="resendInvitation('{{ $invitation->uuid }}')"
                                wire:loading.attr="disabled"
                                wire:target="resendInvitation('{{ $invitation->uuid }}')"
                                class="cursor-pointer disabled:cursor-wait"
                            >
                                <span wire:loading.remove wire:target="resendInvitation('{{ $invitation->uuid }}')">
                                    {{ __('Pošalji ponovno') }}
                                </span>
                                <span wire:loading wire:target="resendInvitation('{{ $invitation->uuid }}')">
                                    {{ __('Šaljem...') }}
                                </span>
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
        </article>
    @empty
        <x-admin-ui::empty-state
            :title="__('Nema pozivnica')"
            :description="__('Poslane pozivnice prikazat će se ovdje.')"
            class="min-h-[13rem] py-8"
        >
            <x-slot:icon>
                <flux:icon icon="inbox" class="size-6" />
            </x-slot:icon>
        </x-admin-ui::empty-state>
    @endforelse

    @if ($invitations->hasPages())
        <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <flux:pagination :paginator="$invitations" scroll-to="#team-invitations-table" />
        </div>
    @endif
</x-admin-ui::panel>
