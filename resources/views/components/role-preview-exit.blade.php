@php
    $preview = app(\IvanBaric\Velora\Support\RolePreview::class);
    $state = $preview->state();
    $roleName = $state
        ? __($state['role_name'] ?? $state['role_slug'] ?? __('Unknown role'))
        : null;
@endphp

@if ($state)
    <div {{ $attributes->merge(['class' => 'border-b border-zinc-200/80 bg-white/90 text-zinc-800 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/90 dark:text-zinc-100']) }}>
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500 dark:text-zinc-400">
                        {{ __('Role preview: :role', ['role' => $roleName]) }}
                    </div>
                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __("You are currently viewing the application with the selected role's permissions.") }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('teams.roles.preview.stop') }}" class="shrink-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold text-zinc-600 underline-offset-4 hover:text-zinc-950 hover:underline dark:text-zinc-300 dark:hover:text-white">
                    {{ __('Exit preview') }}
                </button>
            </form>
        </div>
    </div>
@endif
