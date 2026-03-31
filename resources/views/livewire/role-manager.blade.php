<div>
    <flux:modal wire:model="isOpen" flyout variant="floating" class="space-y-6">
        <div class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="border-b border-zinc-200 bg-gradient-to-br from-white via-zinc-50 to-emerald-50/60 p-6 dark:border-zinc-800 dark:from-zinc-950 dark:via-zinc-950 dark:to-emerald-950/20">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Team access control
                        </div>
                        <flux:heading size="lg">Roles</flux:heading>
                        <flux:subheading class="mt-2">
                            System roles stay locked. Team roles can be created, refined and cleaned up here.
                        </flux:subheading>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <div class="rounded-2xl border border-zinc-200 bg-white/90 px-4 py-3 text-right shadow-xs dark:border-zinc-800 dark:bg-zinc-900/90">
                            <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Visible roles</div>
                            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $roles->count() }}</div>
                        </div>
                        <flux:button wire:click="createRole" variant="primary">New role</flux:button>
                    </div>
                </div>
            </div>

            <div class="space-y-3 bg-zinc-50/80 p-4 dark:bg-zinc-950/70">
                @foreach ($roles as $role)
                    <div class="group rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $role->name }}</div>
                                    @if ($role->isGlobal())
                                        <flux:badge color="zinc">System</flux:badge>
                                    @else
                                        <flux:badge color="emerald">Team</flux:badge>
                                    @endif
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $role->slug }}
                                    </span>
                                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ $role->permission_items_count }} permissions
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @unless ($role->isGlobal())
                                    <flux:button wire:click="editRole('{{ $role->uuid }}')" variant="ghost" size="sm">Edit</flux:button>
                                    <flux:button wire:click="confirmDelete('{{ $role->uuid }}')" variant="danger" size="sm">Delete</flux:button>
                                @else
                                    <div class="text-xs font-medium text-zinc-400 dark:text-zinc-500">Read-only</div>
                                @endunless
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="isFormOpen" variant="floating" class="w-screen max-w-none h-screen max-h-none p-0 m-0 rounded-none">
        <form wire:submit="save" class="h-screen max-h-none overflow-hidden">
            <div class="border-b border-zinc-200 bg-white/95 p-6 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90" x-data="{ selected: @entangle('selectedPermissionItems').live }">
                <div class="mx-auto flex w-full max-w-7xl items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ $roleUuid ? 'Edit role' : 'Create role' }}</flux:heading>
                        <flux:subheading class="mt-1">Give the role a clear name, then choose what it can do.</flux:subheading>
                    </div>
                    <div class="shrink-0 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        <span x-text="(selected?.length ?? 0) + ' selected'"></span>
                    </div>
                </div>
            </div>

            <div class="h-[calc(100vh-160px)] overflow-y-auto bg-zinc-50/70 p-6 dark:bg-zinc-950">
                <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="space-y-6 lg:sticky lg:top-0 lg:self-start">
                        <flux:card class="space-y-6 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Basics</flux:heading>
                                <flux:subheading class="mt-1">Slug is generated automatically from the role name.</flux:subheading>
                            </div>

                            <flux:input wire:model="name" label="Name" placeholder="Editor, Support, Billing..." clearable />

                            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                                Use clear, operational names. Permissions stay editable after creation.
                            </div>
                        </flux:card>

                        <flux:card class="space-y-4 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Permission tools</flux:heading>
                                <flux:subheading class="mt-1">Filter the matrix and speed up repetitive selection.</flux:subheading>
                            </div>

                            <flux:input
                                wire:model.live.debounce.250ms="permissionSearch"
                                label="Search permissions"
                                placeholder="Search by name, label or code..."
                                clearable
                            />

                            <div class="grid gap-2">
                                <flux:button type="button" wire:click="selectAllFilteredPermissions" variant="ghost">Select filtered</flux:button>
                                <flux:button type="button" wire:click="selectAllPermissions" variant="ghost">Select all</flux:button>
                                <flux:button type="button" wire:click="clearPermissions" variant="ghost">Clear selection</flux:button>
                            </div>
                        </flux:card>
                    </div>

                    <div class="space-y-4">
                        @forelse ($permissions as $permissionGroup)
                            <details class="group rounded-3xl border border-zinc-200 bg-white shadow-xs open:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:open:border-zinc-700" open>
                                <summary class="cursor-pointer list-none p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $permissionGroup->label ?: $permissionGroup->name }}</div>
                                            @if ($permissionGroup->description)
                                                <div class="mt-1 max-w-2xl text-xs text-zinc-500">{{ $permissionGroup->description }}</div>
                                            @endif
                                        </div>
                                        <div class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $permissionGroup->items->count() }}</div>
                                    </div>
                                </summary>

                                <div class="border-t border-zinc-200 p-5 dark:border-zinc-800">
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($permissionGroup->items as $item)
                                            <label class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/50 p-3 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                                                <flux:checkbox wire:model="selectedPermissionItems" value="{{ $item->uuid }}" class="mt-0.5" />
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $item->label ?: $item->name }}</div>
                                                    <div class="truncate text-xs text-zinc-500">{{ $item->code }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-3xl border border-zinc-200 bg-white p-6 text-sm text-zinc-600 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                                No permissions match your search.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 bg-white/95 p-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-end gap-2">
                    <flux:button type="button" wire:click="$set('isFormOpen', false)" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save role</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="isDeleteConfirmOpen" variant="floating" class="space-y-6">
        <flux:heading size="lg">Delete role</flux:heading>

        @if ($pendingDeleteUserCount > 0)
            <flux:text>
                This role is currently assigned to {{ $pendingDeleteUserCount }} {{ $pendingDeleteUserCount === 1 ? 'user' : 'users' }}.
                Choose a replacement role to reassign them before deleting.
            </flux:text>

            <flux:select wire:model="replacementRoleUuid" label="Replacement role" placeholder="Select a role" variant="listbox">
                @foreach ($roles->filter(fn ($role) => $role->uuid !== $roleUuid) as $role)
                    <flux:select.option value="{{ $role->uuid }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <flux:text>This role has no users assigned. You can delete it safely.</flux:text>
        @endif

        @error('replacementRoleUuid')
            <div class="text-sm text-red-600">{{ $message }}</div>
        @enderror

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('isDeleteConfirmOpen', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="deleteRole" variant="danger">Delete</flux:button>
        </div>
    </flux:modal>
</div>
