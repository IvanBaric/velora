@if ($modal)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Create team</flux:heading>
            <flux:subheading>Start a new workspace and assign yourself as owner.</flux:subheading>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input wire:model="name" label="Team name" clearable />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$dispatch('close-create-team-modal')" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create team</flux:button>
            </div>
        </form>
    </div>
@else
    <div class="mx-auto max-w-2xl p-6">
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Create team</flux:heading>
                <flux:subheading>Start a new workspace and assign yourself as owner.</flux:subheading>
            </div>

            <form wire:submit="createTeam" class="space-y-6">
                <flux:input wire:model="name" label="Team name" clearable />

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teams.settings') }}" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Create team</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endif
