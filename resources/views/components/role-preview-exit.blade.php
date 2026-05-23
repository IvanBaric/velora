@php
    $preview = app(\IvanBaric\Velora\Support\RolePreview::class);
    $state = $preview->state();
@endphp

@if ($state)
    <div {{ $attributes->merge(['class' => 'border-b border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-100']) }}>
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-200 dark:ring-amber-300/20">
                    <flux:icon icon="eye" class="size-5" />
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold">
                        {{ __('Pregled uloge: :role', ['role' => $state['role_name'] ?? $state['role_slug'] ?? __('Nepoznata uloga')]) }}
                    </div>
                    <div class="text-xs text-amber-800/80 dark:text-amber-100/75">
                        {{ __('Aplikaciju trenutno vidite s dozvolama odabrane uloge.') }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('teams.roles.preview.stop') }}" class="shrink-0">
                @csrf
                @method('DELETE')
                <flux:button type="submit" size="sm" variant="primary" icon="arrow-left">
                    {{ __('Izađi iz pregleda') }}
                </flux:button>
            </form>
        </div>
    </div>
@endif
