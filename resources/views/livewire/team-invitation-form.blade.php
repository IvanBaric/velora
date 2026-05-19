<form wire:submit="sendInvitation" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
    <div class="mb-5 flex items-start gap-3">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
            <flux:icon icon="paper-airplane" variant="micro" class="size-5" />
        </div>
        <div>
            <flux:heading size="sm">{{ __('Nova pozivnica') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pošaljite pozivnicu s unaprijed odabranom ulogom.') }}</flux:text>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="ime@firma.hr" icon="envelope" clearable />

        <flux:select wire:model="roleSlug" label="{{ __('Uloga') }}" variant="listbox">
            @foreach ($roles as $role)
                <flux:select.option value="{{ $role->slug }}">{{ $role->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mt-4 flex justify-end">
        <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Pošalji pozivnicu') }}</flux:button>
    </div>
</form>
