<form wire:submit="sendInvitation" class="space-y-5">
    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
        <flux:input
            wire:model="email"
            label="E-pošta"
            type="email"
            icon="envelope"
            placeholder="ime@tvrtka.hr"
            clearable
        />

        <flux:select wire:model="roleSlug" label="Uloga" variant="listbox">
            @foreach ($roles as $role)
                <flux:select.option value="{{ $role->slug }}">{{ $role->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @error('email')
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    @error('invitations')
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary" icon="paper-airplane">Pošalji pozivnicu</flux:button>
    </div>
</form>
