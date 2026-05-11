<div>
    {{-- Roles list flyout --}}
    <flux:modal wire:model="isOpen" flyout variant="floating" class="space-y-6">
        <div class="flex flex-col gap-4 pe-12 pt-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                        <flux:icon name="shield-check" class="size-5" />
                    </div>
                    <flux:heading size="lg">Uloge</flux:heading>
                </div>
                <flux:subheading>Upravljajte ulogama i pripadajućim dozvolama unutar tima.</flux:subheading>
            </div>

            <flux:button wire:click="createRole" variant="primary" icon="plus">Nova uloga</flux:button>
        </div>

        @if ($roles->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/50 px-6 py-12 text-center dark:border-zinc-800 dark:bg-zinc-900/40">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <flux:icon name="shield-check" class="size-6" />
                </div>
                <div class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">Nema dostupnih uloga</div>
                <div class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                    Stvorite prvu ulogu kako biste mogli dodjeljivati dozvole članovima.
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Naziv</flux:table.column>
                        <flux:table.column>Vrsta</flux:table.column>
                        <flux:table.column>Dozvole</flux:table.column>
                        <flux:table.column class="text-right"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($roles as $role)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            <flux:icon name="shield-check" class="size-4" />
                                        </div>
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $role->name }}</span>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($role->isGlobal())
                                        <flux:badge color="zinc" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="lock-closed" class="size-3.5" />
                                                Sustavna
                                            </span>
                                        </flux:badge>
                                    @else
                                        <flux:badge color="emerald" size="sm" inset="top bottom">
                                            <span class="inline-flex items-center gap-1.5">
                                                <flux:icon name="users" class="size-3.5" />
                                                Timska
                                            </span>
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ $role->permission_items_count }}
                                    </span>
                                </flux:table.cell>

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
                                                        Pregledaj
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.item icon="pencil-square" wire:click="editRole('{{ $role->uuid }}')">
                                                        Uredi
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $role->uuid }}')">
                                                        Izbriši
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
        @endif
    </flux:modal>

    {{-- Role create/edit/view full-screen modal --}}
    <flux:modal wire:model="isFormOpen" variant="floating" class="w-screen max-w-none h-screen max-h-none p-0 m-0 rounded-none">
        <form wire:submit="save" class="flex h-screen max-h-none flex-col" x-data="{ selected: @entangle('selectedPermissionItems').live }">
            {{-- Header --}}
            <div class="border-b border-zinc-200 bg-white/95 p-6 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                            <flux:icon name="shield-check" class="size-5" />
                        </div>
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
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($isReadOnly)
                                    Sustavne uloge nije moguće mijenjati.
                                @else
                                    Konfigurirajte naziv i dozvole za ovu ulogu.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                        <span x-text="(selected?.length ?? 0) + ' odabrano'"></span>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto bg-zinc-50/70 p-6 dark:bg-zinc-950">
                <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[360px_minmax(0,1fr)]">
                    {{-- Sidebar --}}
                    <div class="space-y-5 lg:sticky lg:top-0 lg:self-start">
                        <flux:card class="space-y-5 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Osnovno</flux:heading>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Naziv uloge prikazuje se članovima.</div>
                            </div>

                            <flux:input
                                wire:model="name"
                                label="Naziv"
                                placeholder="npr. Urednik, Podrška, Naplata..."
                                clearable
                                :disabled="$isReadOnly"
                            />

                            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800/60">
                                @if ($isReadOnly)
                                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                                        <flux:icon name="lock-closed" class="size-4" />
                                        <span>Samo za čitanje</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300">
                                        <flux:icon name="pencil-square" class="size-4" />
                                        <span>Uređivo</span>
                                    </div>
                                @endif
                            </div>
                        </flux:card>

                        <flux:card class="space-y-4 rounded-3xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <div>
                                <flux:heading size="sm">Alati za dozvole</flux:heading>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Brzo pretraživanje i odabir.</div>
                            </div>

                            <flux:input
                                wire:model.live.debounce.250ms="permissionSearch"
                                icon="magnifying-glass"
                                placeholder="Pretraži po nazivu, oznaci ili kodu..."
                                clearable
                            />

                            @unless ($isReadOnly)
                                <div class="grid gap-2">
                                    <flux:button type="button" wire:click="selectAllFilteredPermissions" variant="ghost" icon="funnel">
                                        Odaberi filtrirane
                                    </flux:button>
                                    <flux:button type="button" wire:click="selectAllPermissions" variant="ghost" icon="check-circle">
                                        Odaberi sve
                                    </flux:button>
                                    <flux:button type="button" wire:click="clearPermissions" variant="ghost" icon="x-mark">
                                        Poništi odabir
                                    </flux:button>
                                </div>
                            @endunless
                        </flux:card>
                    </div>

                    {{-- Permissions --}}
                    <div class="space-y-4">
                        @forelse ($permissions as $permissionGroup)
                            <details class="group rounded-3xl border border-zinc-200 bg-white shadow-xs open:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:open:border-zinc-700" open>
                                <summary class="cursor-pointer list-none p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                <flux:icon name="{{ $permissionGroup->icon ?: 'square-3-stack-3d' }}" class="size-5" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $permissionGroup->label ?: $permissionGroup->name }}</div>
                                                @if ($permissionGroup->description)
                                                    <div class="mt-0.5 max-w-2xl text-xs text-zinc-500 dark:text-zinc-400">{{ $permissionGroup->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $permissionGroup->items->count() }}
                                        </div>
                                    </div>
                                </summary>

                                <div class="border-t border-zinc-200 p-5 dark:border-zinc-800">
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($permissionGroup->items as $item)
                                            <label class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/50 p-3 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                                                <flux:checkbox wire:model="selectedPermissionItems" value="{{ $item->uuid }}" class="mt-0.5" :disabled="$isReadOnly" />
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $item->label ?: $item->name }}</div>
                                                    <div class="truncate font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $item->code }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-zinc-200 bg-white px-6 py-16 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    <flux:icon name="magnifying-glass" class="size-6" />
                                </div>
                                <div class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">Nema rezultata</div>
                                <div class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                                    Nijedna dozvola ne odgovara vašoj pretrazi.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="border-t border-zinc-200 bg-white/95 p-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-end gap-2">
                    <flux:button type="button" wire:click="$set('isFormOpen', false)" variant="ghost">Odustani</flux:button>
                    @unless ($isReadOnly)
                        <flux:button type="submit" variant="primary" icon="check">Spremi ulogu</flux:button>
                    @endunless
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Delete role confirm --}}
    <flux:modal wire:model="isDeleteConfirmOpen" variant="floating" class="space-y-6 md:w-[460px]">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <div class="flex size-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <flux:icon name="exclamation-triangle" class="size-5" />
                </div>
                <flux:heading size="lg">Izbriši ulogu</flux:heading>
            </div>
            <flux:subheading>Ova radnja se ne može poništiti.</flux:subheading>
        </div>

        @if ($pendingDeleteUserCount > 0)
            <flux:text>
                Ova uloga trenutno je dodijeljena
                <span class="font-semibold text-zinc-900 dark:text-white">{{ $pendingDeleteUserCount }}</span>
                {{ $pendingDeleteUserCount === 1 ? 'korisniku' : 'korisnika' }}.
                Odaberite zamjensku ulogu prije brisanja.
            </flux:text>

            <flux:select wire:model="replacementRoleUuid" label="Zamjenska uloga" placeholder="Odaberite ulogu" variant="listbox">
                @foreach ($roles->filter(fn ($role) => $role->uuid !== $roleUuid) as $role)
                    <flux:select.option value="{{ $role->uuid }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <flux:text>Ova uloga nema dodijeljenih korisnika i može se sigurno izbrisati.</flux:text>
        @endif

        @error('replacementRoleUuid')
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">{{ $message }}</div>
        @enderror

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('isDeleteConfirmOpen', false)" variant="ghost">Odustani</flux:button>
            <flux:button wire:click="deleteRole" variant="danger" icon="trash">Izbriši</flux:button>
        </div>
    </flux:modal>
</div>
