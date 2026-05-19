<div>
    @if ($memberships->isNotEmpty())
        <div class="admin-list-header lg:grid-cols-[minmax(0,1fr)_15rem_8rem_10rem_5rem]">
            <span>{{ __('Korisnik') }}</span>
            <span>{{ __('Email') }}</span>
            <span>{{ __('Status') }}</span>
            <span>{{ __('Uloga') }}</span>
            <span class="text-right">{{ __('Akcije') }}</span>
        </div>
    @endif

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

        <article wire:key="member-{{ $membership->uuid }}" class="admin-list-row p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_15rem_8rem_10rem_5rem]">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-accent/10 text-sm font-semibold uppercase text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                    {{ $initials ?: '?' }}
                </div>

                <div class="min-w-0">
                    <h3 class="truncate text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">
                        {{ $user?->name ?: __('Nepoznat korisnik') }}
                    </h3>
                    <p class="mt-0.5 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                        {{ $membership->joined_at ? __('Pridruzen :date', ['date' => $membership->joined_at->format('d.m.Y.')]) : __('Clanstvo u obradi') }}
                    </p>
                </div>
            </div>

            <div class="min-w-0 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="me-2 text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500 lg:hidden">{{ __('Email') }}</span>
                <span class="truncate lg:block">{{ $user?->email ?: $membership->invited_email }}</span>
            </div>

            <div>
                <span class="me-2 text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500 lg:hidden">{{ __('Status') }}</span>
                <flux:tooltip :content="$membership->status?->tooltip() ?? ''" position="bottom">
                    <span class="inline-flex items-center gap-2">
                        @if ($membership->is_owner)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20">
                                <flux:icon name="key" class="size-3.5" />
                                {{ __('Vlasnik') }}
                            </span>
                        @else
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] ring-1',
                                'bg-accent/10 text-accent-content ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25' => $membership->status === \IvanBaric\Velora\Enums\TeamMembershipStatus::Active,
                                'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20' => $membership->status === \IvanBaric\Velora\Enums\TeamMembershipStatus::Suspended,
                                'bg-zinc-100 text-zinc-600 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10' => $membership->status !== \IvanBaric\Velora\Enums\TeamMembershipStatus::Active && $membership->status !== \IvanBaric\Velora\Enums\TeamMembershipStatus::Suspended,
                            ])>
                                <flux:icon :icon="$membership->status?->icon() ?? 'minus-circle'" class="size-3.5" />
                                {{ ucfirst((string) ($membership->status?->value ?? 'unknown')) }}
                            </span>
                        @endif
                    </span>
                </flux:tooltip>
            </div>

            <div>
                <span class="me-2 text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500 lg:hidden">{{ __('Uloga') }}</span>
                @if ($role)
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <flux:icon icon="shield-check" variant="micro" class="size-4 text-accent-content" />
                        {{ $role->name }}
                    </span>
                @else
                    <x-admin-ui::empty-value />
                @endif
            </div>

            <div class="flex justify-start lg:justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Akcije korisnika') }}" />

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
        </article>
    @empty
        <x-admin-ui::empty-state
            :title="__('Nema clanova')"
            :description="__('Pozovite prvog korisnika kako bi se pojavio u ovom popisu.')"
        >
            <x-slot:icon>
                <flux:icon icon="users" class="size-6" />
            </x-slot:icon>
        </x-admin-ui::empty-state>
    @endforelse

    @if ($memberships->hasPages())
        <div class="border-t border-zinc-100/70 px-4 py-3 dark:border-zinc-800/70">
            {{ $memberships->links() }}
        </div>
    @endif

    <flux:modal wire:model="showMembershipDetailsModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Detalji clanstva') }}</flux:heading>
        </div>

        @if ($membershipDetails)
            <div class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-xl bg-zinc-50/70 p-4 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">{{ __('Korisnik') }}</p>
                    <div class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">{{ data_get($membershipDetails, 'user.name') }}</div>
                    <div class="text-zinc-500 dark:text-zinc-400">{{ data_get($membershipDetails, 'user.email') }}</div>
                </div>

                <div class="rounded-xl bg-zinc-50/70 p-4 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">{{ __('Clanstvo') }}</p>
                    <div class="mt-2"><span class="text-zinc-500">{{ __('Uloga') }}:</span> {{ data_get($membershipDetails, 'role') ?: __('Bez uloge') }}</div>
                    <div><span class="text-zinc-500">{{ __('Vlasnik') }}:</span> {{ data_get($membershipDetails, 'is_owner') ? __('Da') : __('Ne') }}</div>
                </div>

                <div class="rounded-xl bg-zinc-50/70 p-4 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10 sm:col-span-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div><span class="text-zinc-500">{{ __('Pridruzen') }}:</span> {{ data_get($membershipDetails, 'joined_at') ?? '-' }}</div>
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
            <flux:text>{{ __('Ucitavanje...') }}</flux:text>
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
            <flux:heading size="lg">{{ __('Ukloni clana') }}</flux:heading>
        </div>

        <flux:text>{{ __('Ukloniti korisnika :name iz ovog tima?', ['name' => $pendingRemoveUserName]) }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="cancelRemoveMember" variant="ghost">{{ __('Odustani') }}</flux:button>
            <flux:button wire:click="confirmRemoveMember" variant="danger">{{ __('Ukloni') }}</flux:button>
        </div>
    </flux:modal>
</div>
