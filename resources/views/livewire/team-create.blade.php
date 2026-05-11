@if ($modal)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Novi tim</flux:heading>
            <flux:subheading>Kreirajte novi radni prostor i automatski postanite vlasnik.</flux:subheading>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input wire:model="name" label="Naziv tima" clearable />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$dispatch('close-create-team-modal')" variant="ghost">Odustani</flux:button>
                <flux:button type="submit" variant="primary">Kreiraj tim</flux:button>
            </div>
        </form>
    </div>
@else
    <div class="mx-auto max-w-2xl p-6">
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Novi tim</flux:heading>
                <flux:subheading>Kreirajte novi radni prostor i automatski postanite vlasnik.</flux:subheading>
            </div>

            <form wire:submit="createTeam" class="space-y-6">
                <flux:input wire:model="name" label="Naziv tima" clearable />

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teams.settings') }}" variant="ghost">Odustani</flux:button>
                    <flux:button type="submit" variant="primary">Kreiraj tim</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endif
