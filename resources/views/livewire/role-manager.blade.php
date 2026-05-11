<div>
    <flux:modal wire:model="isOpen" flyout variant="floating" class="space-y-6">
        <div class="flex items-center justify-between gap-4 pt-6 pe-12">
            <flux:heading size="lg">Uloge</flux:heading>
            <flux:button wire:click="createRole" variant="primary">Nova uloga</flux:button>
        </div>

        <div class="overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Naziv</flux:table.column>
                    <flux:table.column>Opseg</flux:table.column>
                    <flux:table.column>Dozvole</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($roles as $role)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($role->isGlobal())
                                    <flux:badge color="zinc" size="sm" inset="top bottom">Sustav</flux:badge>
                                @else
                                    <flux:badge color="emerald" size="sm" inset="top bottom">Tim</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $role->permission_items_count }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-2">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon:trailing="ellipsis-horizontal"
                                            aria-label="Akcije uloge"
                                        />

                                        <flux:menu>
                                            @if ($role->isGlobal())
                                                <flux:menu.item icon="eye" wire:click="viewRole('{{ $role->uuid }}')">
                                                    Pregled
                                                </flux:menu.item>
                                            @else
                                                <flux:menu.item icon="pencil-square" wire:click="editRole('{{ $role->uuid }}')">
                                                    Uredi
                                                </flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $role->uuid }}')">
                                                    Obriši
                                                </flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>

    <flux:modal wire:model="isFormOpen" variant="floating" class="w-screen max-w-none h-screen max-h-none p-0 m-0 rounded-none">
        <form wire:submit="save" class="h-screen max-h-none overflow-hidden">
            <div class="border-b border-zinc-200 bg-white/95 p-6 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90" x-data="{ selected: @entangle('selectedPermissionItems').live }">
                <div class="mx-auto flex w-full max-w-7xl items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">
                            @if ($isReadOnly)
                                Pregled uloge
                            @elseif ($roleUuid)
                                Uredi ulogu
                            @else
                                Nova uloga
                            @endif
                        </flux:heading>
                    </div>
                    <div class="shrink-0 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        <span x-text="(selected?.length ?? 0) + ' odabrano'"></span>
                    </div>
                </div>
            </div>

            <div class="h-[calc(100vh-160px)] overflow-y-auto bg-zinc-50/70 p-6 dark:bg-zinc-950">
                <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="space-y-6 lg:sticky lg:top-0 lg:self-start">
                        <flux:card class="space-y-6 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Osnovno</flux:heading>
                            </div>

                            <flux:input
                                wire:model="name"
                                label="Naziv"
                                placeholder="Urednik, Podrška, Naplata..."
                                clearable
                                :disabled="$isReadOnly"
                            />

                            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                                @if ($isReadOnly)
                                    Samo za pregled
                                @else
                                    Može se uređivati
                                @endif
                            </div>
                        </flux:card>

                        <flux:card class="space-y-4 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Alati za dozvole</flux:heading>
                            </div>

                            <flux:input
                                wire:model.live.debounce.250ms="permissionSearch"
                                label="Pretraži dozvole"
                                placeholder="Pretraži po nazivu, oznaci ili kodu..."
                                clearable
                            />

                            @unless ($isReadOnly)
                                <div class="grid gap-2">
                                    <flux:button type="button" wire:click="selectAllFilteredPermissions" variant="ghost">Odaberi filtrirane</flux:button>
                                    <flux:button type="button" wire:click="selectAllPermissions" variant="ghost">Odaberi sve</flux:button>
                                    <flux:button type="button" wire:click="clearPermissions" variant="ghost">Očisti odabir</flux:button>
                                </div>
                            @endunless
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
                                                <flux:checkbox wire:model="selectedPermissionItems" value="{{ $item->uuid }}" class="mt-0.5" :disabled="$isReadOnly" />
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
                                Nijedna dozvola ne odgovara pretrazi.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 bg-white/95 p-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-end gap-2">
                    <flux:button type="button" wire:click="$set('isFormOpen', false)" variant="ghost">Odustani</flux:button>
                    @unless ($isReadOnly)
                        <flux:button type="submit" variant="primary">Spremi ulogu</flux:button>
                    @endunless
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="isDeleteConfirmOpen" variant="floating" class="space-y-6">
        <flux:heading size="lg">Obriši ulogu</flux:heading>

        @if ($pendingDeleteUserCount > 0)
            <flux:text>
                Ova je uloga trenutno dodijeljena za {{ $pendingDeleteUserCount }} {{ $pendingDeleteUserCount === 1 ? 'korisnika' : 'korisnika' }}.
                Odaberite zamjensku ulogu prije brisanja.
            </flux:text>

            <flux:select wire:model="replacementRoleUuid" label="Zamjenska uloga" placeholder="Odaberite ulogu" variant="listbox">
                @foreach ($roles->filter(fn ($role) => $role->uuid !== $roleUuid) as $role)
                    <flux:select.option value="{{ $role->uuid }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <flux:text>Na ovu ulogu nije dodijeljen nijedan korisnik. Možete je obrisati.</flux:text>
        @endif

        @error('replacementRoleUuid')
            <div class="text-sm text-red-600">{{ $message }}</div>
        @enderror

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('isDeleteConfirmOpen', false)" variant="ghost">Odustani</flux:button>
            <flux:button wire:click="deleteRole" variant="danger">Obriši</flux:button>
        </div>
    </flux:modal>
</div>
