<x-dynamic-component :component="config('velora.views.components.auth_layout', 'layouts::auth')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="$existingUser ? __('Pridruži se organizaciji') : __('Dovrši registraciju')"
            :description="$existingUser ? __('Potvrdite članstvo za nastavak.') : __('Kreirajte račun i pridružite se organizaciji.')"
        />

        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">
            <div class="flex items-center justify-between gap-4">
                <span>{{ __('Organizacija') }}</span>
                <strong>{{ $invitation->team->name }}</strong>
            </div>
            <div class="mt-2 flex items-center justify-between gap-4">
                <span>{{ __('Uloga') }}</span>
                <strong>{{ __($roleLabel) }}</strong>
            </div>
            <div class="mt-2 flex items-center justify-between gap-4">
                <span>{{ __('Vrijedi do') }}</span>
                <strong>{{ optional($invitation->expires_at)->format('d.m.Y. H:i') ?? __('Bez isteka') }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('teams.invitation.accept.store', ['token' => $token] + request()->query()) }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="name"
                label="{{ __('Ime') }}"
                :value="old('name', $existingUser?->name)"
                clearable
                :disabled="$existingUser !== null"
            />

            <flux:input name="email" label="{{ __('Email') }}" :value="$invitation->email" clearable disabled />

            @unless ($currentUser && $existingUser && $currentUser->getKey() === $existingUser->getKey())
                <flux:input name="password" label="{{ $existingUser ? __('Lozinka') : __('Postavite lozinku') }}" type="password" clearable required />
            @endunless

            @unless ($existingUser)
                <flux:input name="password_confirmation" label="{{ __('Potvrdite lozinku') }}" type="password" clearable required />
            @endunless

            <flux:button type="submit" variant="primary">
                {{ $existingUser ? __('Prihvati pozivnicu') : __('Kreiraj račun i pridruži se') }}
            </flux:button>
        </form>
    </div>
</x-dynamic-component>
