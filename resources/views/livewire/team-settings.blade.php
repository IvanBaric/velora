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
        ['label' => __('Ukupno članova'), 'value' => number_format($memberCount, 0, ',', ' '), 'icon' => 'users', 'accent' => 'bg-accent'],
        ['label' => __('Aktivni'), 'value' => number_format($activeMemberCount, 0, ',', ' '), 'icon' => 'check-circle', 'accent' => 'bg-accent'],
        ['label' => __('Pozivnice'), 'value' => number_format($pendingInvitationCount, 0, ',', ' '), 'icon' => 'paper-airplane', 'accent' => 'bg-accent'],
        ['label' => __('Uloge'), 'value' => number_format($roleCount, 0, ',', ' '), 'icon' => 'shield-check', 'accent' => 'bg-accent'],
    ];
@endphp

<x-admin-ui::page class="admin-page-compact">
    <x-admin-ui::page-header
        :title="__('Suradnici')"
        :description="__('Upravljajte suradnicima, pozivnicama i ulogama.')"
    >
        <x-slot:actions>
            @if ($canCreateTeam || $canUpdateTeam)
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="building-office">
                        {{ __('Timovi') }}
                    </flux:button>

                    <flux:menu>
                        @if ($canCreateTeam)
                            <flux:menu.item icon="plus" wire:click="openCreateTeamModal">{{ __('Novi tim') }}</flux:menu.item>
                        @endif
                        @if ($canUpdateTeam)
                            <flux:menu.item icon="cog-6-tooth" wire:click="openBasicInfoModal">{{ __('Uredi tim') }}</flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif

            <flux:button variant="ghost" size="sm" icon="shield-check" wire:click="$dispatch('open-role-manager')">
                {{ __('Uloge') }}
            </flux:button>

            @if ($canInviteWithinCurrentPlan ?? true)
                <flux:button variant="primary" icon="envelope" wire:click="$set('showInvitationsModal', true)">
                    {{ __('Pozovi suradnika') }}
                </flux:button>
            @else
                <x-locked-plan-button :tooltip="$invitationBlockedMessage">
                    {{ __('Pozovi suradnika') }}
                </x-locked-plan-button>
            @endif
        </x-slot:actions>
    </x-admin-ui::page-header>

    <x-admin-ui::stat-grid>
        @foreach ($cards as $card)
            <x-admin-ui::stat-card
                :label="$card['label']"
                :value="$card['value']"
                :accent="$card['accent']"
            >
                <x-slot:icon>
                    <flux:icon :icon="$card['icon']" variant="micro" class="size-4" />
                </x-slot:icon>
            </x-admin-ui::stat-card>
        @endforeach
    </x-admin-ui::stat-grid>

    <x-admin-ui::panel loading loading-target="search,clearSearch" loading-text="{{ __('Osvježavam članove') }}">
        <x-admin-ui::panel-header
            :title="__('Suradnici tima')"
            :description="__('Pregled pristupa, statusa i uloga za suradnike trenutnog tima.')"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if ($search !== '')
                        <span class="inline-flex max-w-72 items-center gap-2 rounded-full bg-accent/10 px-3 py-1 text-sm font-medium text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                            <flux:icon icon="magnifying-glass" class="size-4 shrink-0" />
                            <span class="truncate">{{ $search }}</span>
                            <button type="button" wire:click="clearSearch" class="rounded-full p-0.5 text-accent-content/70 transition hover:bg-accent/15 hover:text-accent-content focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/30" aria-label="{{ __('Očisti pretragu') }}">
                                <flux:icon icon="x-mark" class="size-3.5" />
                            </button>
                        </span>
                    @endif

                    <flux:button variant="ghost" size="sm" icon="magnifying-glass" wire:click="$set('showSearchModal', true)">
                        {{ __('Pretraga') }}
                    </flux:button>
                </div>
            </x-slot:actions>
        </x-admin-ui::panel-header>

        @livewire('teams.team-member-manager')
    </x-admin-ui::panel>

    @if (! auth()->user()?->membershipForCurrentTeam()?->is_owner)
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-500/15 dark:bg-zinc-950 dark:ring-red-400/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-red-900 dark:text-red-200">{{ __('Napusti tim') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-red-700 dark:text-red-300">
                        {{ __('Ova akcija uklanja vas pristup timu :team.', ['team' => $team->name]) }}
                    </p>
                </div>

                <flux:button wire:click="$set('showLeaveTeamModal', true)" variant="danger" size="sm">
                    {{ __('Napusti tim') }}
                </flux:button>
            </div>
        </section>
    @endif

    @livewire('roles.role-manager')

    <flux:modal wire:model="showSearchModal" x-on:close="$wire.closeSearchModal()" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Pretraga članova') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Filtrirajte listu članova po imenu ili email adresi.') }}</flux:text>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" label="{{ __('Pretraga') }}" placeholder="{{ __('Upišite ime ili email...') }}" icon="magnifying-glass" clearable />

        <div class="flex justify-end">
            <flux:button type="button" variant="ghost" icon="x-mark" wire:click="closeSearchModal">
                {{ __('Zatvori') }}
            </flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showInvitationsModal" flyout variant="floating" class="space-y-8">
        <div class="space-y-6 pt-4">
            <div>
                <flux:heading size="lg">{{ __('Pozivnice') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pozovite nove suradnike i pratite status već poslanih pozivnica.') }}</flux:text>
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
                <flux:heading size="lg">{{ __('Uredi tim') }}</flux:heading>
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
</x-admin-ui::page>
