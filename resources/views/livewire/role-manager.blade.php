<div>
    <flux:modal wire:model="isOpen" flyout variant="floating" class="w-full max-w-xl space-y-6">
        <div class="space-y-5 pt-6 pe-10">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ __('Uloge') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Upravljajte pristupom suradnika organizacije kroz jasno definirane uloge i dozvole.') }}
                    </flux:text>
                </div>

                @if ($rolesAndPermissionsAvailable)
                    <flux:button wire:click="createRole" variant="primary" icon="plus">{{ __('Nova uloga') }}</flux:button>
                @else
                    <x-locked-plan-button :tooltip="$roleManagementBlockedMessage">
                        {{ __('Nova uloga') }}
                    </x-locked-plan-button>
                @endif
            </div>

            @unless ($rolesAndPermissionsAvailable)
                <x-plan-notice :message="__('Uloge i dozvole nisu uključene u trenutačni plan. Postojeći pristup ostaje aktivan, ali upravljanje ulogama zaključano je do nadogradnje plana.')" />
            @endunless

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-zinc-50/70 p-4 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">{{ __('Ukupno uloga') }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-zinc-950 dark:text-white">{{ $roles->count() }}</p>
                </div>

                <div class="rounded-2xl bg-zinc-50/70 p-4 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:ring-white/10">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">{{ __('Organizacijske uloge') }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-zinc-950 dark:text-white">{{ $roles->filter(fn ($role) => ! $role->isGlobal())->count() }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            @foreach ($roles as $role)
                @php
                    $permissionCount = (int) $role->permission_items_count;
                    $permissionLabel = $permissionCount % 10 >= 2 && $permissionCount % 10 <= 4 && ($permissionCount % 100 < 12 || $permissionCount % 100 > 14)
                        ? __('dozvole')
                        : __('dozvola');
                @endphp

                <article class="group rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-950/5 transition duration-150 ease-out hover:bg-zinc-50/50 focus-within:bg-zinc-50/60 dark:bg-zinc-950 dark:ring-white/10 dark:hover:bg-zinc-900/60 dark:focus-within:bg-zinc-900/70">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">{{ __($role->name) }}</h3>

                                @if ($role->isGlobal())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] text-zinc-600 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10">
                                        <flux:icon name="lock-closed" class="size-3.5" />
                                        {{ __('Sustav') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                                        <flux:icon name="users" class="size-3.5" />
                                        {{ __('Organizacija') }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 text-[13px] leading-5 text-zinc-500 dark:text-zinc-400">
                                {{ number_format($permissionCount, 0, ',', ' ') }} {{ $permissionLabel }}
                            </p>
                        </div>

                        <flux:dropdown position="bottom" align="end">
                            <flux:tooltip :content="__('Otvori akcije za ovu ulogu')">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('Akcije uloge') }}" />
                            </flux:tooltip>

                            <flux:menu>
                                <form method="POST" action="{{ route('teams.roles.preview', ['role' => $role]) }}">
                                    @csrf
                                    <flux:menu.item icon="arrow-right-circle" type="submit">
                                        {{ __('Pregledaj kao ovu ulogu') }}
                                    </flux:menu.item>
                                </form>

                                <flux:menu.separator />

                                @if ($role->isGlobal())
                                    <flux:menu.item icon="eye" wire:click="viewRole('{{ $role->uuid }}')">
                                        {{ __('Pregled') }}
                                    </flux:menu.item>
                                @else
                                    <flux:menu.item icon="pencil-square" wire:click="editRole('{{ $role->uuid }}')">
                                        {{ __('Uredi') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $role->uuid }}')">
                                        {{ __('Obriši') }}
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </article>
            @endforeach
        </div>
    </flux:modal>

    <flux:modal wire:model="isFormOpen" variant="floating" class="m-0 h-[100dvh] max-h-[100dvh] w-screen max-w-none overflow-hidden rounded-none p-0">
        <form wire:submit="save" class="flex h-[100dvh] max-h-[100dvh] flex-col overflow-hidden">
            <div class="shrink-0 border-b border-zinc-100/70 bg-white/95 p-6 backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-950/90" x-data="{ selected: @entangle('selectedPermissionItems').live }">
                <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <flux:heading size="lg">
                            @if ($isReadOnly)
                                {{ __('Pregled uloge') }}
                            @elseif ($roleUuid)
                                {{ __('Uredi ulogu') }}
                            @else
                                {{ __('Nova uloga') }}
                            @endif
                        </flux:heading>
                        <flux:text class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Odaberite koje dijelove administracije ova uloga može koristiti.') }}
                        </flux:text>
                    </div>

                    @unless ($isReadOnly)
                        <flux:button type="submit" variant="primary" icon="check">
                            <span>{{ __('Spremi ulogu') }}</span>
                            <span class="text-xs opacity-80" x-text="'(' + (selected?.length ?? 0) + ' ' + @js(__('odabrano')) + ')'"></span>
                        </flux:button>
                    @else
                        <div class="inline-flex w-fit items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-sm font-medium text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                            <flux:icon icon="check-circle" class="size-4" />
                            <span x-text="(selected?.length ?? 0) + ' ' + @js(__('odabrano'))"></span>
                        </div>
                    @endunless
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-zinc-50/70 p-6 dark:bg-zinc-950">
                <div class="mx-auto grid max-w-7xl items-start gap-8 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <aside class="space-y-4 lg:sticky lg:top-0 lg:self-start">
                        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                                    <flux:icon icon="identification" class="size-5" />
                                </div>
                                <flux:heading size="sm">{{ __('Osnovno') }}</flux:heading>
                            </div>

                            <div class="space-y-4">
                                <flux:input wire:model="name" label="{{ __('Naziv') }}" placeholder="{{ __('Urednik, Podrška, Naplata...') }}" clearable :disabled="$isReadOnly" />

                                @if ($isReadOnly)
                                    <div class="rounded-2xl bg-zinc-50/70 px-4 py-3 text-sm text-zinc-600 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:text-zinc-300 dark:ring-white/10">
                                        <div class="flex items-start gap-2">
                                            <flux:icon icon="lock-closed" class="mt-0.5 size-4 shrink-0" />
                                            <span>{{ __('Sistemske uloge dostupne su samo za pregled.') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </aside>

                    <div class="space-y-4">
                        @forelse ($permissions as $permissionGroup)
                            <details class="group rounded-2xl bg-white shadow-sm ring-1 ring-zinc-950/5 transition duration-150 ease-out open:ring-zinc-950/10 dark:bg-zinc-950 dark:ring-white/10 dark:open:ring-white/15">
                                <summary class="cursor-pointer list-none p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <flux:icon icon="chevron-right" class="size-4 shrink-0 text-zinc-400 transition duration-150 ease-out group-open:rotate-90 dark:text-zinc-500" />
                                                <div class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ __($permissionGroup->label ?: $permissionGroup->name) }}</div>
                                            </div>
                                            @if ($permissionGroup->description)
                                                <div class="mt-1 max-w-2xl ps-6 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ __($permissionGroup->description) }}</div>
                                            @endif
                                        </div>

                                        <div class="shrink-0 rounded-full bg-accent/10 px-2.5 py-1 text-xs font-medium tabular-nums text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">{{ $permissionGroup->items->count() }}</div>
                                    </div>
                                </summary>

                                <div class="border-t border-zinc-100/70 p-5 dark:border-zinc-800/70">
                                    <div class="grid gap-2 md:grid-cols-2">
                                        @foreach ($permissionGroup->items as $item)
                                            <label class="flex min-w-0 items-start gap-3 rounded-2xl bg-zinc-50/70 p-3 ring-1 ring-zinc-950/5 transition duration-150 ease-out hover:bg-white hover:ring-zinc-950/10 focus-within:bg-white focus-within:ring-zinc-950/15 dark:bg-zinc-900/80 dark:ring-white/10 dark:hover:bg-zinc-900 dark:hover:ring-white/15 dark:focus-within:bg-zinc-900 dark:focus-within:ring-white/20">
                                                <flux:checkbox wire:model="selectedPermissionItems" value="{{ $item->uuid }}" class="mt-0.5" :disabled="$isReadOnly" />
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-medium text-zinc-950 dark:text-white">{{ __($item->label ?: $item->name) }}</span>
                                                    <span class="mt-0.5 block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ __($item->code) }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10">
                                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-50 text-zinc-400 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-white/10">
                                    <flux:icon icon="magnifying-glass" class="size-5" />
                                </div>
                                <p class="mt-4 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Nema pronađenih dozvola') }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Nijedna dozvola ne odgovara pretrazi.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="isDeleteConfirmOpen" variant="floating" class="space-y-6">
        <div class="flex items-start gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/20">
                <flux:icon icon="trash" class="size-5" />
            </div>
            <div>
                <flux:heading size="lg">{{ __('Obriši ulogu') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Ova radnja uklanja ulogu iz organizacije. Suradnici koji je koriste moraju dobiti zamjensku ulogu.') }}
                </flux:text>
            </div>
        </div>

        @if ($pendingDeleteUserCount > 0)
            @php
                $pendingUserLabel = $pendingDeleteUserCount % 10 >= 2 && $pendingDeleteUserCount % 10 <= 4 && ($pendingDeleteUserCount % 100 < 12 || $pendingDeleteUserCount % 100 > 14)
                    ? __('suradnika')
                    : __('suradnika');
            @endphp

            <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">
                {{ $pendingDeleteUserCount === 1 ? __('Ova je uloga trenutno dodijeljena jednom suradniku.') : __('Ova je uloga trenutno dodijeljena za :count :label.', ['count' => number_format($pendingDeleteUserCount, 0, ',', ' '), 'label' => $pendingUserLabel]) }}
            </div>

            <flux:select wire:model="replacementRoleUuid" label="{{ __('Zamjenska uloga') }}" placeholder="{{ __('Odaberite ulogu') }}" variant="listbox">
                @foreach ($roles->filter(fn ($role) => $role->uuid !== $roleUuid) as $role)
                    <flux:select.option value="{{ $role->uuid }}">{{ __($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <div class="rounded-2xl bg-zinc-50/70 p-4 text-sm text-zinc-600 ring-1 ring-zinc-950/5 dark:bg-zinc-900/80 dark:text-zinc-300 dark:ring-white/10">
                {{ __('Na ovu ulogu nije dodijeljen nijedan suradnik. Možete je obrisati bez zamjenske uloge.') }}
            </div>
        @endif

        @error('replacementRoleUuid')
            <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror

        <div class="flex justify-end gap-2 border-t border-zinc-100/70 pt-4 dark:border-zinc-800/70">
            <flux:button wire:click="$set('isDeleteConfirmOpen', false)" variant="ghost">{{ __('Odustani') }}</flux:button>
            <flux:button wire:click="deleteRole" variant="danger">{{ __('Obriši') }}</flux:button>
        </div>
    </flux:modal>
</div>
