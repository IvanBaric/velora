<form wire:submit="sendInvitation" class="space-y-6">
    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
        <flux:input wire:model="email" label="Email" type="email" placeholder="name@company.com" clearable />

        <flux:select wire:model="roleSlug" label="Role" variant="listbox">
            @foreach ($roles as $role)
                <flux:select.option value="{{ $role->slug }}">{{ $role->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary">Send invitation</flux:button>
    </div>
</form>
