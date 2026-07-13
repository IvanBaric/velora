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
    $showTeamMenu = $canCreateTeam || $canUpdateTeam || $canLeaveTeam;

    $cards = [
        ['label' => __('Ukupno suradnika'), 'value' => number_format($memberCount, 0, ',', ' '), 'icon' => 'users', 'accent' => 'bg-zinc-900 dark:bg-white'],
        ['label' => __('Aktivni'), 'value' => number_format($activeMemberCount, 0, ',', ' '), 'icon' => 'check-circle', 'accent' => 'bg-emerald-500'],
        ['label' => __('Pozivnice'), 'value' => number_format($pendingInvitationCount, 0, ',', ' '), 'icon' => 'paper-airplane', 'accent' => 'bg-sky-500'],
        ['label' => __('Uloge'), 'value' => number_format($roleCount, 0, ',', ' '), 'icon' => 'shield-check', 'accent' => 'bg-amber-400'],
    ];
@endphp

<x-admin-ui::page>
    <x-admin-ui::page-header
        :title="__('Suradnici')"
        :description="__('Upravljajte suradnicima, pozivnicama i ulogama.')"
        icon="users"
    >
        <x-slot:actions>
            @if ($showTeamMenu)
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="building-office">
                        {{ __('Organizacija') }}
                    </flux:button>

                    <flux:menu>
                        @if ($canCreateTeam)
                            <flux:menu.item icon="plus" wire:click="openCreateTeamModal">{{ __('Nova organizacija') }}</flux:menu.item>
                        @endif

                        @if ($canUpdateTeam)
                            <flux:menu.item icon="cog-6-tooth" wire:click="openBasicInfoModal">{{ __('Uredi organizaciju') }}</flux:menu.item>
                        @endif

                        @if (($canCreateTeam || $canUpdateTeam) && $canLeaveTeam)
                            <flux:menu.separator />
                        @endif

                        @if ($canLeaveTeam)
                            <flux:menu.item icon="arrow-right-start-on-rectangle" variant="danger" wire:click="openLeaveTeamModal">
                                {{ __('Napusti organizaciju') }}
                            </flux:menu.item>
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

    <x-admin-ui::panel loading loading-target="search,clearSearch" loading-text="{{ __('Osvježavam suradnike') }}">
        <x-admin-ui::panel-header
            :title="__('Suradnici organizacije')"
            :description="__('Pregled pristupa, statusa i uloga za suradnike trenutne organizacije.')"
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

    @livewire('roles.role-manager')

    <flux:modal wire:model="showSearchModal" x-on:close="$wire.closeSearchModal()" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Pretraga suradnika') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Filtrirajte listu suradnika po imenu ili email adresi.') }}</flux:text>
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
                <flux:heading size="lg">{{ __('Nova organizacija') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kreirajte dodatni radni prostor i automatski postanite vlasnik.') }}</flux:text>
            </div>

            <form wire:submit="createTeam" wire:loading.class="admin-panel-content-loading" wire:target="createTeam" class="relative space-y-6">
                <x-admin-ui::loading-overlay target="createTeam" :text="__('Spremanje...')" />
                <flux:input wire:model="createTeamName" label="{{ __('Naziv organizacije') }}" placeholder="{{ __('Npr. Učenička zadruga Zagreb') }}" clearable data-required />

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="closeCreateTeamModal" variant="ghost">{{ __('Odustani') }}</flux:button>
                    <x-admin-ui::submit-button target="createTeam" icon="plus">{{ __('Kreiraj organizaciju') }}</x-admin-ui::submit-button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($canUpdateTeam)
        <flux:modal wire:model="showBasicInfoModal" class="space-y-6 md:w-[30rem]">
            <div class="space-y-2">
                <div class="flex size-11 items-center justify-center rounded-lg bg-pink-50 text-pink-600 ring-1 ring-pink-200 dark:bg-pink-500/10 dark:text-pink-300 dark:ring-pink-400/20">
                    <flux:icon icon="building-office" class="size-5" />
                </div>
                <flux:heading size="lg">{{ __('Uredi organizaciju') }}</flux:heading>
                <flux:text class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                    {{ __('Promijenite naziv koji se prikazuje u administraciji i postavkama organizacije.') }}
                </flux:text>
            </div>

            <form wire:submit="updateTeamName" wire:loading.class="admin-panel-content-loading" wire:target="updateTeamName" class="relative space-y-6">
                <x-admin-ui::loading-overlay target="updateTeamName" :text="__('Spremanje...')" />
                <flux:input
                    wire:model="name"
                    label=""
                    :placeholder="__('Unesite naziv organizacije...')"
                    clearable
                    data-required
                />

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="$set('showBasicInfoModal', false)" variant="ghost">{{ __('Odustani') }}</flux:button>
                    <x-admin-ui::submit-button target="updateTeamName">{{ __('Spremi') }}</x-admin-ui::submit-button>
                </div>
            </form>
        </flux:modal>
    @endif

    <flux:modal wire:model="showLeaveTeamModal" class="space-y-6 md:w-[30rem]">
        <div class="space-y-2">
            <div class="flex size-11 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/20">
                <flux:icon icon="arrow-right-start-on-rectangle" class="size-5" />
            </div>
            <flux:heading size="lg">{{ __('Napusti organizaciju') }}</flux:heading>
            <flux:text class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                {{ __('Ova radnja uklanja vaš pristup organizaciji :team.', ['team' => $team->name]) }}
            </flux:text>
        </div>

        @if ($leaveTeamUnavailableMessage)
            <div class="admin-inset-panel p-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                {{ $leaveTeamUnavailableMessage }}
            </div>

            <div class="flex justify-end">
                <flux:button type="button" wire:click="closeLeaveTeamModal" variant="ghost">{{ __('Zatvori') }}</flux:button>
            </div>
        @else
            <form wire:submit="confirmLeaveTeam" class="space-y-6">
                <div class="admin-inset-panel p-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    {{ __('Za potvrdu unesite svoju lozinku. Ovo sprječava slučajno napuštanje organizacije. Ako se predomislite, vlasnik ili administrator morat će vam poslati novu pozivnicu.') }}
                </div>

                <flux:input
                    wire:model="leaveTeamPassword"
                    :label="__('Lozinka')"
                    type="password"
                    autocomplete="current-password"
                    data-required
                    viewable
                />

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="closeLeaveTeamModal" variant="ghost">{{ __('Odustani') }}</flux:button>
                    <flux:button type="submit" variant="danger" icon="arrow-right-start-on-rectangle">{{ __('Napusti organizaciju') }}</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</x-admin-ui::page>
