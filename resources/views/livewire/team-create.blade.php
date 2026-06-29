@if ($modal)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Nova organizacija') }}</flux:heading>
            <flux:subheading>{{ __('Kreirajte novi radni prostor i automatski postanite vlasnik.') }}</flux:subheading>
        </div>

        <form wire:submit="createTeam" class="space-y-6">
            <flux:input wire:model="name" label="{{ __('Naziv organizacije') }}" clearable />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$dispatch('close-create-team-modal')" variant="ghost">{{ __('Odustani') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Kreiraj organizaciju') }}</flux:button>
            </div>
        </form>
    </div>
@else
    <x-admin-ui::page class="max-w-2xl">
        <x-admin-ui::panel class="space-y-6 p-6">
            <div>
                <flux:heading size="lg">{{ __('Nova organizacija') }}</flux:heading>
                <flux:subheading>{{ __('Kreirajte novi radni prostor i automatski postanite vlasnik.') }}</flux:subheading>
            </div>

            <form wire:submit="createTeam" class="space-y-6">
                <flux:input wire:model="name" label="{{ __('Naziv organizacije') }}" clearable />

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teams.settings') }}" variant="ghost">{{ __('Odustani') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Kreiraj organizaciju') }}</flux:button>
                </div>
            </form>
        </x-admin-ui::panel>
    </x-admin-ui::page>
@endif
