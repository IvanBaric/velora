@php
    use IvanBaric\Velora\Enums\TeamInvitationStatus;
    use IvanBaric\Velora\Enums\TeamMembershipStatus;
    use IvanBaric\Velora\Models\Role;
    use IvanBaric\Velora\Models\TeamInvitation;
    use IvanBaric\Velora\Models\TeamMembership;

    $teamId = $team->getKey();
    $memberCount = TeamMembership::query()->withoutGlobalScopes()->where('team_id', $teamId)->count();
    $activeMemberCount = TeamMembership::query()->withoutGlobalScopes()->where('team_id', $teamId)->where('status', TeamMembershipStatus::Active->value)->count();
    $pendingInvitationCount = TeamInvitation::query()->where('team_id', $teamId)->where('status', TeamInvitationStatus::Pending->value)->count();
    $roleCount = Role::query()->availableToTeam($teamId)->notHidden()->count();

    $cards = [
        ['label' => __('Ukupno članova'), 'value' => number_format($memberCount, 0, ',', ' '), 'icon' => 'users', 'accent' => 'bg-zinc-900 dark:bg-white'],
        ['label' => __('Aktivni'), 'value' => number_format($activeMemberCount, 0, ',', ' '), 'icon' => 'check-circle', 'accent' => 'bg-emerald-500'],
        ['label' => __('Pozivnice'), 'value' => number_format($pendingInvitationCount, 0, ',', ' '), 'icon' => 'paper-airplane', 'accent' => 'bg-sky-500'],
        ['label' => __('Uloge'), 'value' => number_format($roleCount, 0, ',', ' '), 'icon' => 'shield-check', 'accent' => 'bg-violet-500'],
    ];
@endphp

<section class="mx-auto grid w-full max-w-7xl gap-7 pb-4 lg:gap-8">
    <header class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
        <div class="max-w-2xl space-y-2.5">
            <flux:heading size="xl" level="1" class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Korisnici') }}</flux:heading>
            <flux:subheading class="max-w-xl text-[15px] leading-7 text-zinc-500 dark:text-zinc-400">
                {{ __('Upravljajte članovima, pozivnicama i ulogama za tim :team.', ['team' => $team->name]) }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:tooltip :content="__('Pretraži članove')">
                <flux:button variant="ghost" icon="magnifying-glass" size="sm" wire:click="$set('showSearchModal', true)">
                    {{ __('Pretraga') }}
                </flux:button>
            </flux:tooltip>
            <flux:tooltip :content="__('Uredi uloge i dozvole')">
                <flux:button variant="ghost" icon="shield-check" size="sm" wire:click="$dispatch('open-role-manager')">
                    {{ __('Uloge') }}
                </flux:button>
            </flux:tooltip>
            <flux:button variant="primary" icon="envelope" wire:click="$set('showInvitationsModal', true)">
                {{ __('Pozovi korisnika') }}
            </flux:button>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 transition duration-150 ease-out dark:bg-zinc-950 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-2.5">
                        <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold tabular-nums tracking-tight text-zinc-950 dark:text-white">{{ $card['value'] }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50/80 p-2.5 text-zinc-400 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:text-zinc-500 dark:ring-white/10">
                        <flux:icon :icon="$card['icon']" variant="micro" class="size-4" />
                    </div>
                </div>
                <div class="mt-5 h-0.5 w-8 rounded-full opacity-70 {{ $card['accent'] }}"></div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
        <div class="p-2">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="rounded-xl bg-zinc-50/70 p-1 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                    <div class="flex flex-wrap items-center gap-1">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-zinc-950 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-800 dark:text-white dark:ring-white/10">
                            <span>{{ __('Članovi') }}</span>
                            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[11px] tabular-nums text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">{{ $memberCount }}</span>
                        </button>
                        <button type="button" wire:click="$set('showInvitationsModal', true)" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-500 transition duration-150 ease-out hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            <span>{{ __('Pozivnice') }}</span>
                            <span class="text-[11px] tabular-nums text-zinc-400 dark:text-zinc-500">{{ $pendingInvitationCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3 lg:w-auto">
                    <div class="lg:w-80">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Pretraži po imenu ili emailu...') }}"
                            size="sm"
                            icon="magnifying-glass"
                            clearable
                        />
                    </div>
                    @if ($canCreateTeam || $canUpdateTeam)
                        <flux:dropdown position="bottom" align="end">
                            <flux:tooltip :content="__('Dodatne akcije')">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            </flux:tooltip>

                            <flux:menu>
                                @if ($canCreateTeam)
                                    <flux:menu.item icon="plus" wire:click="openCreateTeamModal">{{ __('Novi tim') }}</flux:menu.item>
                                @endif
                                @if ($canUpdateTeam)
                                    <flux:menu.item icon="cog-6-tooth" wire:click="openBasicInfoModal">{{ __('Postavke tima') }}</flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            </div>
        </div>

        @livewire('teams.team-member-manager')
    </div>

    @if (! auth()->user()?->membershipForCurrentTeam()?->is_owner)
        <div class="rounded-2xl bg-red-50/80 p-5 shadow-sm ring-1 ring-red-200/70 dark:bg-red-950/20 dark:ring-red-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-red-900 dark:text-red-200">{{ __('Napusti tim') }}</div>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ __('Ova akcija uklanja vaš pristup timu :team.', ['team' => $team->name]) }}</p>
                </div>
                <flux:button wire:click="$set('showLeaveTeamModal', true)" variant="danger" size="sm">{{ __('Napusti tim') }}</flux:button>
            </div>
        </div>
    @endif

    @livewire('roles.role-manager')

    <flux:modal wire:model="showSearchModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Pretraga članova') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Filtrirajte tablicu članova po imenu ili email adresi.') }}</flux:text>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" label="{{ __('Pretraga') }}" placeholder="{{ __('Upišite ime ili email...') }}" icon="magnifying-glass" clearable />
    </flux:modal>

    <flux:modal wire:model="showInvitationsModal" flyout variant="floating" class="space-y-8">
        <div class="space-y-6 pt-4">
            <div>
                <flux:heading size="lg">{{ __('Pozivnice') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pozovite nove korisnike i pratite status već poslanih pozivnica.') }}</flux:text>
            </div>

            <div class="space-y-6">
                @livewire('teams.team-invitation-form')
                @livewire('teams.team-invitation-manager')
            </div>
        </div>
    </flux:modal>

    @if ($canCreateTeam)
    <flux:modal wire:model="showCreateTeamModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Novi tim') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kreirajte dodatni radni prostor i automatski postanite vlasnik.') }}</flux:text>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input wire:model="createTeamName" label="{{ __('Naziv tima') }}" placeholder="{{ __('Npr. Prodaja Zagreb') }}" clearable />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeCreateTeamModal" variant="ghost">{{ __('Odustani') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Kreiraj tim') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif

    @if ($canUpdateTeam)
    <flux:modal wire:model="showBasicInfoModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Postavke tima') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Osnovni naziv koji se prikazuje u korisničkom sučelju.') }}</flux:text>
        </div>

        <form wire:submit="updateTeamName" class="space-y-6">
            <flux:input wire:model="name" label="{{ __('Naziv tima') }}" placeholder="{{ __('Unesite naziv tima...') }}" clearable />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ __('Spremi') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif

    <flux:modal wire:model="showLeaveTeamModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Napusti tim') }}</flux:heading>
        </div>

        <flux:text>{{ __('Želite napustiti tim :team?', ['team' => $team->name]) }}</flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showLeaveTeamModal', false)" variant="ghost">{{ __('Odustani') }}</flux:button>
            <flux:button wire:click="confirmLeaveTeam" variant="danger">{{ __('Napusti') }}</flux:button>
        </div>
    </flux:modal>
</section>
