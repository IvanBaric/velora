<form wire:submit="sendInvitation" wire:loading.class="admin-panel-content-loading" wire:target="sendInvitation" class="admin-inset-panel relative p-4">
    <x-admin-ui::loading-overlay target="sendInvitation" :text="__('Spremanje...')" />
    <div @class(['flex items-start gap-3', 'mb-4' => $canInviteWithinCurrentPlan ?? true])>
        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
            <flux:icon icon="paper-airplane" variant="micro" class="size-4" />
        </div>
        <div>
            <flux:heading size="sm">{{ __('Nova pozivnica') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pošaljite pozivnicu s unaprijed odabranom ulogom.') }}</flux:text>
        </div>
    </div>

    @if (! ($canInviteWithinCurrentPlan ?? true))
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem]">
            <flux:input label="{{ __('Email') }}" type="email" placeholder="ime@domena.hr" icon="envelope" disabled />

            <flux:select label="{{ __('Uloga') }}" variant="listbox" disabled>
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->slug }}">{{ __($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-4">
            <x-plan-notice
                :heading="__('Značajka nije dostupna na ovom planu')"
                :message="$invitationBlockedMessage"
            />
        </div>

        <div class="mt-3 flex justify-end">
            <x-locked-plan-button :tooltip="$invitationBlockedMessage">
                {{ __('Pošalji pozivnicu') }}
            </x-locked-plan-button>
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem]">
            <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="ime@domena.hr" icon="envelope" clearable data-required />

            <flux:select wire:model="roleSlug" label="{{ __('Uloga') }}" variant="listbox" data-required>
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->slug }}">{{ __($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-3 flex justify-end">
            <flux:tooltip :content="__('Pošalji pozivnicu na unesenu email adresu')">
                <x-admin-ui::submit-button target="sendInvitation" icon="paper-airplane">{{ __('Pošalji pozivnicu') }}</x-admin-ui::submit-button>
            </flux:tooltip>
        </div>
    @endif
</form>
