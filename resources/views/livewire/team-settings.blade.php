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

<x-admin-ui::page>
    <x-admin-ui::page-header
        :title="__('Korisnici')"
        :description="__('Upravljajte članovima, pozivnicama i ulogama.')"
    >
        <x-slot:actions>
            <flux:button variant="ghost" size="sm" icon="magnifying-glass" wire:click="$set('showSearchModal', true)">
                {{ __('Pretraga') }}
            </flux:button>

            <flux:button variant="ghost" size="sm" icon="shield-check" wire:click="$dispatch('open-role-manager')">
                {{ __('Uloge') }}
            </flux:button>

            <flux:button variant="primary" icon="envelope" wire:click="$set('showInvitationsModal', true)">
                {{ __('Pozovi korisnika') }}
            </flux:button>
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

    <x-admin-ui::toolbar-stack>
        <x-admin-ui::filter-card>
            <div class="flex min-w-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="admin-filter-tabs">
                    <div class="admin-filter-tabs-list">
                        <button type="button" disabled class="admin-filter-tab admin-filter-tab-active">
                            <span>{{ __('Članovi') }}</span>
                            <span class="admin-filter-count admin-filter-count-active">{{ $memberCount }}</span>
                        </button>

                        <button type="button" wire:click="$set('showInvitationsModal', true)" class="admin-filter-tab admin-filter-tab-inactive">
                            <span>{{ __('Pozivnice') }}</span>
                            <span class="admin-filter-count admin-filter-count-inactive">{{ $pendingInvitationCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                    <div class="sm:w-80">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Pretraži po imenu ili emailu...') }}"
                            size="sm"
                            icon="magnifying-glass"
                            clearable
                            aria-label="{{ __('Pretraži članove') }}"
                        />
                    </div>

                    @if ($canCreateTeam || $canUpdateTeam)
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Dodatne akcije') }}" />

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
        </x-admin-ui::filter-card>
    </x-admin-ui::toolbar-stack>

    <x-admin-ui::panel loading loading-target="search" loading-text="{{ __('Osvježavam članove') }}">
        <x-admin-ui::panel-header
            :title="__('Članovi tima')"
            :description="__('Pregled pristupa, statusa i uloga za trenutni tim.')"
        />

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

    <flux:modal wire:model="showSearchModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Pretraga članova') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Filtrirajte listu članova po imenu ili email adresi.') }}</flux:text>
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
</x-admin-ui::page>
