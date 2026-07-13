@if ($modal)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Nova organizacija') }}</flux:heading>
            <flux:subheading>{{ __('Kreirajte novi radni prostor i automatski postanite vlasnik.') }}</flux:subheading>
        </div>

        <form wire:submit="createTeam" wire:loading.class="admin-panel-content-loading" wire:target="createTeam" class="relative space-y-6">
            <x-admin-ui::loading-overlay target="createTeam" :text="__('Spremanje...')" />
            <flux:input wire:model="name" label="{{ __('Naziv organizacije') }}" clearable data-required />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$dispatch('close-create-team-modal')" variant="ghost">{{ __('Odustani') }}</flux:button>
                <x-admin-ui::submit-button target="createTeam" icon="plus">{{ __('Kreiraj organizaciju') }}</x-admin-ui::submit-button>
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

            <form wire:submit="createTeam" wire:loading.class="admin-panel-content-loading" wire:target="createTeam" class="relative space-y-6">
                <x-admin-ui::loading-overlay target="createTeam" :text="__('Spremanje...')" />
                <flux:input wire:model="name" label="{{ __('Naziv organizacije') }}" clearable data-required />

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teams.settings') }}" variant="ghost">{{ __('Odustani') }}</flux:button>
                    <x-admin-ui::submit-button target="createTeam" icon="plus">{{ __('Kreiraj organizaciju') }}</x-admin-ui::submit-button>
                </div>
            </form>
        </x-admin-ui::panel>
    </x-admin-ui::page>
@endif
