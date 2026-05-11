@php
    use IvanBaric\Velora\Enums\TeamMembershipStatus;

    $currentUser = auth()->user();
    $currentMembership = $currentUser?->membershipForCurrentTeam();
    $isOwner = (bool) ($currentMembership?->is_owner ?? false);

    $teamInitials = collect(preg_split('/\s+/u', trim((string) $team->name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    if ($teamInitials === '') {
        $teamInitials = 'T';
    }

    $totalMembers = $team->memberships()->where('status', TeamMembershipStatus::Active->value)->count();
    $pendingInvitationsCount = \IvanBaric\Velora\Models\TeamInvitation::query()
        ->where('team_id', $team->getKey())
        ->where('status', \IvanBaric\Velora\Enums\TeamInvitationStatus::Pending->value)
        ->count();
    $rolesCount = \IvanBaric\Velora\Models\Role::query()
        ->availableToTeam($team->getKey())
        ->notHidden()
        ->count();
@endphp

<div class="mx-auto max-w-6xl space-y-6 px-6 py-6 lg:px-8 lg:py-8">
    {{-- Header --}}
    <section
        class="border-b border-zinc-200 pb-6 dark:border-zinc-800"
    >
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-5">
                <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-xl font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    {{ $teamInitials }}
                </div>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="users" class="size-3.5" />
                        Radni prostor tima
                    </div>
                    <flux:heading size="xl" class="!text-2xl lg:!text-3xl">{{ $team->name }}</flux:heading>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                        Upravljajte članovima, ulogama i postavkama svog tima na jednom mjestu.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button
                    variant="primary"
                    icon="paper-airplane"
                    wire:click="$set('showInvitationsModal', true)"
                >
                    Pošalji pozivnicu
                </flux:button>

                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="filled" icon="ellipsis-vertical" aria-label="Akcije tima" />

                    <flux:menu>
                        <flux:menu.item icon="envelope" wire:click="$set('showInvitationsModal', true)">
                            Pozivnice
                        </flux:menu.item>
                        <flux:menu.item icon="plus-circle" wire:click="openCreateTeamModal">
                            Stvori novi tim
                        </flux:menu.item>
                        <flux:menu.item icon="shield-check" wire:click="$dispatch('open-role-manager')">
                            Uloge
                        </flux:menu.item>
                        <flux:menu.item icon="cog-6-tooth" wire:click="$set('showBasicInfoModal', true)">
                            Postavke
                        </flux:menu.item>
                        <flux:menu.item icon="magnifying-glass" wire:click="$set('showSearchModal', true)">
                            Pretraga
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="grid gap-6 border-b border-zinc-200 pb-6 dark:border-zinc-800 sm:grid-cols-3">
        <div class="border-l border-zinc-200 pl-4 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Aktivni članovi</div>
                <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <flux:icon name="users" class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $totalMembers }}</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Trenutno aktivnih u timu</div>
        </div>

        <div class="border-l border-zinc-200 pl-4 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Pozivnice na čekanju</div>
                <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <flux:icon name="envelope" class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $pendingInvitationsCount }}</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Čekaju prihvaćanje</div>
        </div>

        <div class="border-l border-zinc-200 pl-4 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <div class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Dostupne uloge</div>
                <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <flux:icon name="shield-check" class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $rolesCount }}</div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sustavne i timske uloge</div>
        </div>
    </section>

    {{-- Members section --}}
    <section class="space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">Članovi</flux:heading>
                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ $totalMembers }}</flux:badge>
                </div>
                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Pregled svih korisnika koji imaju pristup timu.
                </div>
            </div>

            <div class="flex items-center gap-2">
                <flux:button
                    variant="ghost"
                    icon="magnifying-glass"
                    wire:click="$set('showSearchModal', true)"
                >
                    Pretraži
                </flux:button>
                <flux:button
                    variant="filled"
                    icon="user-plus"
                    wire:click="$set('showInvitationsModal', true)"
                >
                    Pozovi
                </flux:button>
            </div>
        </header>

        <div>
            @livewire('teams.team-member-manager')
        </div>
    </section>

    {{-- Danger zone --}}
    @if (! $isOwner)
        <section class="border-t border-red-200 pt-6 dark:border-red-900/60">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300">
                        <flux:icon name="exclamation-triangle" class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-red-900 dark:text-red-200">Napuštanje tima</div>
                        <div class="mt-1 text-sm text-red-800/80 dark:text-red-300/80">
                            Izgubit ćete pristup svim sadržajima ovog tima. Ova radnja se ne može poništiti.
                        </div>
                    </div>
                </div>
                <flux:button wire:click="$set('showLeaveTeamModal', true)" variant="danger" icon="arrow-right-start-on-rectangle">
                    Napusti {{ $team->name }}
                </flux:button>
            </div>
        </section>
    @endif

    @livewire('roles.role-manager')

    {{-- Search modal --}}
    <flux:modal wire:model="showSearchModal" class="space-y-6 md:w-[480px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <flux:icon name="magnifying-glass" class="size-5" />
                </div>
                <flux:heading size="lg">Pretraga članova</flux:heading>
            </div>
            <flux:subheading>Filtrirajte popis članova prema imenu ili e-pošti.</flux:subheading>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Upišite ime ili e-poštu..."
            clearable
        />

        <div class="flex justify-end">
            <flux:button wire:click="$set('showSearchModal', false)" variant="ghost">Zatvori</flux:button>
        </div>
    </flux:modal>

    {{-- Invitations flyout --}}
    <flux:modal wire:model="showInvitationsModal" flyout variant="floating" class="space-y-8">
        <div class="space-y-2 pe-12">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                    <flux:icon name="envelope" class="size-5" />
                </div>
                <flux:heading size="lg">Pozivnice</flux:heading>
            </div>
            <flux:subheading>Pozovite nove članove i upravljajte poslanim pozivnicama.</flux:subheading>
        </div>

        <div class="border-b border-zinc-200 pb-6 dark:border-zinc-800">
            <div class="mb-4">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Nova pozivnica</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Pošaljite pozivnicu putem e-pošte i odaberite ulogu.</div>
            </div>
            @livewire('teams.team-invitation-form')
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Poslane pozivnice</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">Pregled statusa svih pozivnica</div>
            </div>
            @livewire('teams.team-invitation-manager')
        </div>
    </flux:modal>

    {{-- Create team modal --}}
    <flux:modal wire:model="showCreateTeamModal" class="space-y-6 md:w-[460px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <flux:icon name="plus-circle" class="size-5" />
                </div>
                <flux:heading size="lg">Stvori novi tim</flux:heading>
            </div>
            <flux:subheading>Pokrenite novi radni prostor i postanite njegov vlasnik.</flux:subheading>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input
                wire:model="createTeamName"
                label="Naziv tima"
                placeholder="npr. Marketing, Razvoj, Podrška..."
                clearable
            />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeCreateTeamModal" variant="ghost">Odustani</flux:button>
                <flux:button type="submit" variant="primary" icon="plus">Stvori tim</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Settings modal --}}
    <flux:modal wire:model="showBasicInfoModal" class="space-y-6 md:w-[460px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <flux:icon name="cog-6-tooth" class="size-5" />
                </div>
                <flux:heading size="lg">Postavke tima</flux:heading>
            </div>
            <flux:subheading>Ažurirajte osnovne informacije o vašem timu.</flux:subheading>
        </div>

        <form wire:submit="updateTeamName" class="space-y-6">
            <flux:input
                wire:model="name"
                label="Naziv tima"
                placeholder="Unesite naziv tima..."
                clearable
            />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showBasicInfoModal', false)" variant="ghost">Odustani</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Spremi</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Leave team confirm --}}
    <flux:modal wire:model="showLeaveTeamModal" class="space-y-6 md:w-[440px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <flux:icon name="exclamation-triangle" class="size-5" />
                </div>
                <flux:heading size="lg">Napusti tim</flux:heading>
            </div>
            <flux:subheading>Ova radnja se ne može poništiti.</flux:subheading>
        </div>

        <flux:text>
            Sigurni ste da želite napustiti tim <span class="font-semibold text-zinc-900 dark:text-white">{{ $team->name }}</span>?
            Izgubit ćete pristup svim resursima ovog tima.
        </flux:text>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('showLeaveTeamModal', false)" variant="ghost">Odustani</flux:button>
            <flux:button wire:click="confirmLeaveTeam" variant="danger" icon="arrow-right-start-on-rectangle">Napusti tim</flux:button>
        </div>
    </flux:modal>
</div>
