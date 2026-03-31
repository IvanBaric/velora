<form wire:submit="sendInvitation" class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
    <div class="space-y-6">
        <div>
            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Invite teammate</div>
            <div class="mt-1 text-sm text-zinc-500">Assign a role up front so new members land with the right access.</div>
        </div>

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
    </div>
</form>
