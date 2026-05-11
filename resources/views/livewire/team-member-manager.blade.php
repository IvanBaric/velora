<div>
    <div class="hidden grid-cols-12 gap-3 bg-zinc-50/40 px-6 py-3 text-[11px] font-medium uppercase tracking-[0.16em] text-zinc-400 dark:bg-zinc-900/30 dark:text-zinc-500 sm:grid">
        <div class="col-span-4">{{ __('Korisnik') }}</div>
        <div class="col-span-3">{{ __('Email') }}</div>
        <div class="col-span-2">{{ __('Status') }}</div>
        <div class="col-span-2">{{ __('Uloga') }}</div>
        <div class="col-span-1 text-right">{{ __('Akcije') }}</div>
    </div>

    @forelse ($memberships as $membership)
        @php
            $user = $membership->user;
            $initials = collect(explode(' ', trim((string) $user?->name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_substr($part, 0, 1))
                ->implode('');
            $role = $membership->roles->first();
        @endphp

        <div wire:key="member-{{ $membership->uuid }}" class="group/row grid grid-cols-1 items-center gap-3 border-t border-zinc-100/70 px-4 py-4 transition duration-150 ease-out first:border-t-0 hover:bg-zinc-50/50 focus-within:bg-zinc-50/60 dark:border-zinc-800/70 dark:hover:bg-zinc-900/60 dark:focus-within:bg-zinc-900/70 sm:grid-cols-12 sm:px-6">
            <div class="col-span-4 flex min-w-0 items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-50 text-sm font-semibold uppercase text-zinc-600 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10">
                    {{ $initials ?: '?' }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">{{ $user?->name ?: __('Nepoznat korisnik') }}</div>
                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $membership->joined_at ? __('Pridružen :date', ['date' => $membership->joined_at->format('d.m.Y.')]) : __('Članstvo u obradi') }}
                    </div>
                </div>
            </div>

            <div class="col-span-3 min-w-0 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="truncate sm:block">{{ $user?->email ?: $membership->invited_email }}</span>
            </div>

            <div class="col-span-2">
                <flux:tooltip :content="$membership->status?->tooltip() ?? ''" position="bottom">
                    <div class="inline-flex items-center gap-2">
                        @if ($membership->is_owner)
                            <flux:badge color="amber" size="sm" inset="top bottom" icon="key">{{ __('Vlasnik') }}</flux:badge>
                        @else
                            <flux:badge color="{{ $membership->status?->color() ?? 'zinc' }}" size="sm" inset="top bottom" icon="{{ $membership->status?->icon() ?? 'minus-circle' }}">
                                {{ ucfirst((string) ($membership->status?->value ?? 'unknown')) }}
                            </flux:badge>
                        @endif
                    </div>
                </flux:tooltip>
            </div>

            <div class="col-span-2">
                @if ($role)
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <flux:icon icon="shield-check" variant="micro" class="size-4 text-zinc-400" />
                        {{ $role->name }}
                    </span>
                @else
                    <span class="text-sm text-zinc-400 dark:text-zinc-500">-</span>
                @endif
            </div>

            <div class="col-span-1 flex justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:tooltip :content="__('Akcije korisnika')">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Akcije korisnika') }}" />
                    </flux:tooltip>

                    <flux:menu>
                        <flux:menu.item icon="information-circle" wire:click="openMembershipDetails('{{ $membership->uuid }}')">
                            {{ __('Detalji') }}
                        </flux:menu.item>

                        @if (! $membership->is_owner)
                            <flux:menu.item icon="shield-check" wire:click="requestRoleChange('{{ $membership->uuid }}')">
                                {{ __('Promijeni ulogu') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="requestRemoveMember('{{ $membership->uuid }}')">
                                {{ __('Ukloni') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    @empty
        <div class="p-6 sm:p-10">
            <div class="mx-auto flex max-w-md flex-col items-center rounded-2xl bg-zinc-50/70 px-6 py-12 text-center ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-white text-zinc-400 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:text-zinc-500 dark:ring-white/10">
                    <flux:icon icon="users" class="size-6" />
                </div>
                <p class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Nema članova') }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pozovite prvog korisnika kako bi se pojavio u ovom popisu.') }}</p>
            </div>
        </div>
    @endforelse

    @if ($memberships->hasPages())
        <div class="border-t border-zinc-100/70 px-4 py-3 dark:border-zinc-800/70">
            {{ $memberships->links() }}
        </div>
    @endif

    <flux:modal wire:model="showMembershipDetailsModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Detalji članstva') }}</flux:heading>
        </div>

        @if ($membershipDetails)
            <div class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Korisnik') }}</div>
                    <div class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">{{ data_get($membershipDetails, 'user.name') }}</div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ data_get($membershipDetails, 'user.email') }}</div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Članstvo') }}</div>
                    <div class="mt-2"><span class="text-zinc-500">{{ __('Uloga') }}:</span> {{ data_get($membershipDetails, 'role') ?: __('Bez uloge') }}</div>
                    <div><span class="text-zinc-500">{{ __('Vlasnik') }}:</span> {{ data_get($membershipDetails, 'is_owner') ? __('Da') : __('Ne') }}</div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div><span class="text-zinc-500">{{ __('Pridružen') }}:</span> {{ data_get($membershipDetails, 'joined_at') ?? '-' }}</div>
                        <div><span class="text-zinc-500">{{ __('Zadnja aktivnost') }}:</span> {{ data_get($membershipDetails, 'last_seen_at') ?? '-' }}</div>
                        @if (data_get($membershipDetails, 'invited_email'))
                            <div><span class="text-zinc-500">{{ __('Pozvan email') }}:</span> {{ data_get($membershipDetails, 'invited_email') }}</div>
                        @endif
                        @if (data_get($membershipDetails, 'invited_by_name') || data_get($membershipDetails, 'invited_by_email'))
                            <div><span class="text-zinc-500">{{ __('Pozvao') }}:</span> {{ data_get($membershipDetails, 'invited_by_name') ?: data_get($membershipDetails, 'invited_by_email') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <flux:text>{{ __('Učitavanje...') }}</flux:text>
        @endif

        <div class="flex justify-end">
            <flux:button wire:click="closeMembershipDetails" variant="ghost">{{ __('Zatvori') }}</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRoleChangeModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Promijeni ulogu') }}</flux:heading>
            @if ($pendingRoleUserName)
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $pendingRoleUserName }}</flux:text>
            @endif
        </div>
        <flux:select wire:model="pendingRole" label="{{ __('Uloga') }}" variant="listbox">
            @foreach ($availableRoles as $slug => $roleName)
                <flux:select.option value="{{ $slug }}">{{ $roleName }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRoleChange" variant="ghost">{{ __('Odustani') }}</flux:button>
            <flux:button wire:click="confirmRoleChange" variant="primary">{{ __('Spremi') }}</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRemoveMemberModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Ukloni člana') }}</flux:heading>
        </div>
        <flux:text>{{ __('Ukloniti korisnika :name iz ovog tima?', ['name' => $pendingRemoveUserName]) }}</flux:text>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRemoveMember" variant="ghost">{{ __('Odustani') }}</flux:button>
            <flux:button wire:click="confirmRemoveMember" variant="danger">{{ __('Ukloni') }}</flux:button>
        </div>
    </flux:modal>
</div>
