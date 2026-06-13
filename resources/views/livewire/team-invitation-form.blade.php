<form wire:submit="sendInvitation" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
    <div @class(['flex items-start gap-3', 'mb-4' => $canInviteWithinCurrentPlan ?? true])>
        <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
            <flux:icon icon="paper-airplane" variant="micro" class="size-4" />
        </div>
        <div>
            <flux:heading size="sm">{{ __('New invitation') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Send an invitation with a preselected role.') }}</flux:text>
        </div>
    </div>

    @if (! ($canInviteWithinCurrentPlan ?? true))
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem]">
            <flux:input label="{{ __('Email') }}" type="email" placeholder="name@company.com" icon="envelope" disabled />

            <flux:select label="{{ __('Role') }}" variant="listbox" disabled>
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->slug }}">{{ __($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-4">
            <x-plan-notice
                :heading="__('Feature unavailable on this plan')"
                :message="$invitationBlockedMessage"
            />
        </div>

        <div class="mt-3 flex justify-end">
            <x-locked-plan-button :tooltip="$invitationBlockedMessage">
                {{ __('Send invitation') }}
            </x-locked-plan-button>
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem]">
            <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="name@company.com" icon="envelope" clearable />

            <flux:select wire:model="roleSlug" label="{{ __('Role') }}" variant="listbox">
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->slug }}">{{ __($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-3 flex justify-end">
            <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Send invitation') }}</flux:button>
        </div>
    @endif
</form>
