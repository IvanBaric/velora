@php
    $statusLabels = [
        'active' => 'Aktivan',
        'suspended' => 'Suspendiran',
        'revoked' => 'Opozvan',
    ];
    $statusTooltips = [
        'active' => 'Aktivan član tima.',
        'suspended' => 'Privremeno onemogućen pristup.',
        'revoked' => 'Pristup uklonjen.',
    ];

    $initialsFor = function (string $name): string {
        $initials = collect(preg_split('/\s+/u', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '?';
    };

    $avatarPalette = [
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300',
    ];

    $colorFor = function (string $key) use ($avatarPalette): string {
        $index = abs(crc32($key)) % count($avatarPalette);

        return $avatarPalette[$index];
    };
@endphp

<div class="space-y-4">
    @if ($memberships->isEmpty())
        <div class="flex flex-col items-center justify-center border-y border-dashed border-zinc-200 px-6 py-12 text-center dark:border-zinc-800">
            <div class="flex size-11 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <flux:icon name="users" class="size-6" />
            </div>
            <div class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">Nema pronađenih članova</div>
            <div class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                Pokušajte s drugim pojmom za pretragu ili pošaljite novu pozivnicu.
            </div>
        </div>
    @else
        <div class="overflow-x-auto border-y border-zinc-200 dark:border-zinc-800">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Član</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Uloga</flux:table.column>
                    <flux:table.column class="text-right"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($memberships as $membership)
                        @php
                            $userName = (string) ($membership->user->name ?? '—');
                            $userEmail = (string) ($membership->user->email ?? '');
                            $statusKey = (string) ($membership->status?->value ?? 'unknown');
                        @endphp
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $colorFor($userEmail ?: $userName) }}">
                                        {{ $initialsFor($userName) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $userName }}</div>
                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $userEmail }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($membership->is_owner)
                                    <flux:tooltip content="Vlasnik tima ima puni pristup." position="bottom">
                                        <flux:badge color="amber" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="key" class="size-4" />
                                                Vlasnik
                                            </span>
                                        </flux:badge>
                                    </flux:tooltip>
                                @else
                                    <flux:tooltip :content="$statusTooltips[$statusKey] ?? ''" position="bottom">
                                        <flux:badge color="{{ $membership->status?->color() ?? 'zinc' }}" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="{{ $membership->status?->icon() ?? 'minus-circle' }}" class="size-4" />
                                                {{ $statusLabels[$statusKey] ?? ucfirst($statusKey) }}
                                            </span>
                                        </flux:badge>
                                    </flux:tooltip>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $roleName = $membership->roles->first()?->name;
                                @endphp
                                @if ($roleName)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        <flux:icon name="shield-check" class="size-3.5" />
                                        {{ $roleName }}
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Bez uloge</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon:trailing="ellipsis-horizontal"
                                            aria-label="Akcije člana"
                                        />

                                        <flux:menu>
                                            <flux:menu.item icon="information-circle" wire:click="openMembershipDetails('{{ $membership->uuid }}')">
                                                Detalji
                                            </flux:menu.item>

                                            @if (! $membership->is_owner)
                                                <flux:menu.item icon="shield-check" wire:click="requestRoleChange('{{ $membership->uuid }}')">
                                                    Promijeni ulogu
                                                </flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger" wire:click="requestRemoveMember('{{ $membership->uuid }}')">
                                                    Ukloni iz tima
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

    <div class="pt-2">
        {{ $memberships->links() }}
    </div>

    {{-- Membership details modal --}}
    <flux:modal wire:model="showMembershipDetailsModal" class="space-y-6 md:w-[560px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <flux:icon name="information-circle" class="size-5" />
                </div>
                <flux:heading size="lg">Detalji članstva</flux:heading>
            </div>
            <flux:subheading>Pregled svih informacija o članu i njegovom članstvu.</flux:subheading>
        </div>

        @if ($membershipDetails)
            @php
                $detailStatusKey = (string) data_get($membershipDetails, 'status');
                $detailStatus = \IvanBaric\Velora\Enums\TeamMembershipStatus::tryFrom($detailStatusKey);
                $detailUserName = (string) data_get($membershipDetails, 'user.name');
                $detailUserEmail = (string) data_get($membershipDetails, 'user.email');
            @endphp

            <div class="space-y-5 text-sm">
                {{-- Member summary --}}
                <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full text-base font-semibold {{ $colorFor($detailUserEmail ?: $detailUserName) }}">
                            {{ $initialsFor($detailUserName) }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-zinc-900 dark:text-white">{{ $detailUserName }}</div>
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $detailUserEmail }}</div>
                        </div>
                    </div>
                </div>

                {{-- Membership info grid --}}
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Status</div>
                        <div class="mt-2">
                            @if ($detailStatus)
                                <flux:badge color="{{ $detailStatus->color() }}" size="sm" inset="top bottom">
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon name="{{ $detailStatus->icon() }}" class="size-4" />
                                        {{ $statusLabels[$detailStatus->value] ?? ucfirst($detailStatus->value) }}
                                    </span>
                                </flux:badge>
                            @else
                                <span class="text-zinc-500">{{ $detailStatusKey }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Uloga</div>
                        <div class="mt-2 font-medium text-zinc-900 dark:text-white">
                            {{ data_get($membershipDetails, 'role') ?: 'Bez uloge' }}
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Vlasnik</div>
                        <div class="mt-2 font-medium text-zinc-900 dark:text-white">
                            {{ data_get($membershipDetails, 'is_owner') ? 'Da' : 'Ne' }}
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">UUID</div>
                        <div class="mt-2 break-all font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ data_get($membershipDetails, 'uuid') }}
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Pridružio se</div>
                        <div class="mt-2 text-zinc-700 dark:text-zinc-200">
                            {{ data_get($membershipDetails, 'joined_at') ?? '—' }}
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                        <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Posljednja aktivnost</div>
                        <div class="mt-2 text-zinc-700 dark:text-zinc-200">
                            {{ data_get($membershipDetails, 'last_seen_at') ?? '—' }}
                        </div>
                    </div>
                </div>

                @if (data_get($membershipDetails, 'invited_email') || data_get($membershipDetails, 'invited_by_name') || data_get($membershipDetails, 'invited_by_email'))
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="envelope" class="size-3.5" />
                            Pozivnica
                        </div>
                        <div class="mt-3 space-y-2 text-sm">
                            @if (data_get($membershipDetails, 'invited_email'))
                                <div>
                                    <span class="text-zinc-500">Pozvana e-pošta:</span>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ data_get($membershipDetails, 'invited_email') }}</span>
                                </div>
                            @endif
                            @if (data_get($membershipDetails, 'invited_by_name') || data_get($membershipDetails, 'invited_by_email'))
                                <div>
                                    <span class="text-zinc-500">Pozvao:</span>
                                    <span class="font-medium text-zinc-900 dark:text-white">
                                        {{ data_get($membershipDetails, 'invited_by_name') ?: data_get($membershipDetails, 'invited_by_email') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <flux:text>Učitavanje...</flux:text>
        @endif

        <div class="flex justify-end">
            <flux:button wire:click="closeMembershipDetails" variant="ghost">Zatvori</flux:button>
        </div>
    </flux:modal>

    {{-- Change role modal --}}
    <flux:modal wire:model="showRoleChangeModal" class="space-y-6 md:w-[440px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <flux:icon name="shield-check" class="size-5" />
                </div>
                <flux:heading size="lg">Promijeni ulogu</flux:heading>
            </div>
            <flux:subheading>
                @if ($pendingRoleUserName)
                    Odaberite novu ulogu za <span class="font-semibold text-zinc-900 dark:text-white">{{ $pendingRoleUserName }}</span>.
                @else
                    Odaberite novu ulogu za odabranog člana.
                @endif
            </flux:subheading>
        </div>

        <flux:select wire:model="pendingRole" label="Uloga" variant="listbox">
            @foreach ($availableRoles as $slug => $roleName)
                <flux:select.option value="{{ $slug }}">{{ $roleName }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRoleChange" variant="ghost">Odustani</flux:button>
            <flux:button wire:click="confirmRoleChange" variant="primary" icon="check">Spremi</flux:button>
        </div>
    </flux:modal>

    {{-- Remove member modal --}}
    <flux:modal wire:model="showRemoveMemberModal" class="space-y-6 md:w-[440px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <flux:icon name="exclamation-triangle" class="size-5" />
                </div>
                <flux:heading size="lg">Ukloni člana</flux:heading>
            </div>
            <flux:subheading>Ova radnja se ne može poništiti.</flux:subheading>
        </div>

        <flux:text>
            Sigurni ste da želite ukloniti
            <span class="font-semibold text-zinc-900 dark:text-white">{{ $pendingRemoveUserName }}</span>
            iz ovog tima?
        </flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRemoveMember" variant="ghost">Odustani</flux:button>
            <flux:button wire:click="confirmRemoveMember" variant="danger" icon="trash">Ukloni</flux:button>
        </div>
    </flux:modal>
</div>
