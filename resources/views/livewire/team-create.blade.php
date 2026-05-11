@if ($modal)
    <div class="space-y-6">
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
                wire:model="name"
                label="Naziv tima"
                placeholder="npr. Marketing, Razvoj, Podrška..."
                clearable
            />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$dispatch('close-create-team-modal')" variant="ghost">Odustani</flux:button>
                <flux:button type="submit" variant="primary" icon="plus">Stvori tim</flux:button>
            </div>
        </form>
    </div>
@else
    <div class="mx-auto max-w-xl p-6">
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-zinc-50 to-zinc-100 shadow-xs dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-950">
            <div class="pointer-events-none absolute -right-16 -top-16 hidden h-48 w-48 rounded-full bg-emerald-200/30 blur-3xl dark:bg-emerald-500/10 sm:block"></div>

            <div class="relative space-y-6 p-8">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-sky-500 text-white shadow-lg shadow-emerald-500/20">
                        <flux:icon name="plus-circle" class="size-6" />
                    </div>
                    <div>
                        <flux:heading size="lg">Stvori novi tim</flux:heading>
                        <flux:subheading>Pokrenite novi radni prostor i postanite njegov vlasnik.</flux:subheading>
                    </div>
                </div>

                <form wire:submit="createTeam" class="space-y-6">
                    <flux:input
                        wire:model="name"
                        label="Naziv tima"
                        placeholder="npr. Marketing, Razvoj, Podrška..."
                        clearable
                    />

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('teams.settings') }}" variant="ghost">Odustani</flux:button>
                        <flux:button type="submit" variant="primary" icon="plus">Stvori tim</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
