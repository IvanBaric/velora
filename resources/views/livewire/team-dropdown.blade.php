@if (($variant ?? 'dropdown') === 'menu')
    <div class="contents">
        @foreach ($allTeams as $loopTeam)
            @php($isCurrentTeam = $currentTeam->uuid === $loopTeam->uuid)

            @if ($isCurrentTeam)
                <flux:menu.item disabled>
                    <div class="flex w-full items-center justify-between">
                        <span>{{ $loopTeam->name }}</span>
                        <flux:icon name="check" class="size-4" />
                    </div>
                </flux:menu.item>
            @else
                <flux:menu.item href="{{ route('teams.switch', $loopTeam) }}">
                    <div class="flex w-full items-center justify-between text-shadow-zinc-800">
                        <span>{{ $loopTeam->name }}</span>
                    </div>
                </flux:menu.item>
            @endif
        @endforeach
    </div>
@else
    <flux:dropdown class="px-2">
        <flux:button
            variant="ghost"
            icon-trailing="chevron-down"
            class="justify-between rounded-xl bg-zinc-100 px-3 py-2 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700"
        >
            {{ $currentTeam->name }}
        </flux:button>
        <flux:menu class="rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($allTeams as $loopTeam)
                @php($isCurrentTeam = $currentTeam->uuid === $loopTeam->uuid)

                @if ($isCurrentTeam)
                    <flux:menu.item disabled>
                        <div class="flex w-full items-center justify-between text-shadow-zinc-500">
                            <span>{{ $loopTeam->name }}</span>
                            <flux:icon name="check" class="size-4" />
                        </div>
                    </flux:menu.item>
                @else
                    <flux:menu.item href="{{ route('teams.switch', $loopTeam) }}">
                        <div class="flex w-full items-center justify-between text-shadow-zinc-800">
                            <span>{{ $loopTeam->name }}</span>
                        </div>
                    </flux:menu.item>
                @endif
            @endforeach
        </flux:menu>
    </flux:dropdown>
@endif
