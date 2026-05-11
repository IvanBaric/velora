@php
    $invitationStatusLabels = [
        'pending' => 'Na čekanju',
        'accepted' => 'Prihvaćena',
        'revoked' => 'Opozvana',
        'expired' => 'Istekla',
    ];
    $invitationStatusTooltips = [
        'pending' => 'Pozivnica čeka odgovor primatelja.',
        'accepted' => 'Pozivnica je prihvaćena.',
        'revoked' => 'Pozivnica je opozvana.',
        'expired' => 'Pozivnica je istekla.',
    ];
@endphp

<div class="space-y-4">
    @if ($invitations->isEmpty())
        <div class="flex flex-col items-center justify-center border-y border-dashed border-zinc-200 px-6 py-10 text-center dark:border-zinc-800">
            <div class="flex size-11 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <flux:icon name="inbox" class="size-5" />
            </div>
            <div class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">Još nema pozivnica</div>
            <div class="mt-1 max-w-sm text-xs text-zinc-500 dark:text-zinc-400">
                Pošaljite prvu pozivnicu pomoću obrasca iznad.
            </div>
        </div>
    @else
        <div class="overflow-x-auto border-y border-zinc-200 dark:border-zinc-800">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>E-pošta</flux:table.column>
                    <flux:table.column>Uloga</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="text-right"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($invitations as $invitation)
                        @php
                            $statusKey = (string) $invitation->status->value;
                        @endphp
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        <flux:icon name="envelope" class="size-4" />
                                    </div>
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $invitation->email }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    <flux:icon name="shield-check" class="size-3.5" />
                                    {{ $roleLabels[$invitation->role_slug] ?? $invitation->role_slug }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:tooltip :content="$invitationStatusTooltips[$statusKey] ?? ''" position="bottom">
                                    <flux:badge color="{{ $invitation->status->color() }}" size="sm" inset="top bottom">
                                        <span class="inline-flex items-center gap-1.5">
                                            <flux:icon name="{{ $invitation->status->icon() }}" class="size-4" />
                                            {{ $invitationStatusLabels[$statusKey] ?? ucfirst($statusKey) }}
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
                                            aria-label="Akcije pozivnice"
                                        />

                                        <flux:menu>
                                            @if ($invitation->status !== \IvanBaric\Velora\Enums\TeamInvitationStatus::Accepted)
                                                <flux:menu.item icon="paper-airplane" wire:click="resendInvitation('{{ $invitation->uuid }}')">
                                                    Ponovno pošalji
                                                </flux:menu.item>
                                            @endif

                                            @if (in_array($invitation->status, [\IvanBaric\Velora\Enums\TeamInvitationStatus::Pending, \IvanBaric\Velora\Enums\TeamInvitationStatus::Expired], true))
                                                <flux:menu.item icon="trash" variant="danger" wire:click="revokeInvitation('{{ $invitation->uuid }}')">
                                                    Opozovi
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
    @endif

    @error('invitations')
        <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">{{ $message }}</div>
    @enderror

    <div class="pt-2">
        {{ $invitations->links() }}
    </div>
</div>
