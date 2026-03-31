<x-dynamic-component :component="config('velora.views.components.auth_layout', 'layouts.auth')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="$existingUser ? 'Join team' : 'Complete signup'"
            :description="$existingUser ? 'Confirm your membership to continue.' : 'Create your account and join the team.'"
        />

        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">
            <div class="flex items-center justify-between gap-4">
                <span>Team</span>
                <strong>{{ $invitation->team->name }}</strong>
            </div>
            <div class="mt-2 flex items-center justify-between gap-4">
                <span>Role</span>
                <strong>{{ $roleLabel }}</strong>
            </div>
            <div class="mt-2 flex items-center justify-between gap-4">
                <span>Valid until</span>
                <strong>{{ optional($invitation->expires_at)->format('d.m.Y. H:i') ?? 'No expiry' }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('teams.invitation.accept.store', ['token' => $token] + request()->query()) }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="name"
                label="Name"
                :value="old('name', $existingUser?->name)"
                clearable
                :disabled="$existingUser !== null"
            />

            <flux:input name="email" label="Email" :value="$invitation->email" clearable disabled />

            @unless ($currentUser && $existingUser && $currentUser->getKey() === $existingUser->getKey())
                <flux:input name="password" label="{{ $existingUser ? 'Password' : 'Set password' }}" type="password" clearable required />
            @endunless

            @unless ($existingUser)
                <flux:input name="password_confirmation" label="Confirm password" type="password" clearable required />
            @endunless

            <flux:button type="submit" variant="primary">
                {{ $existingUser ? 'Accept invitation' : 'Create account and join' }}
            </flux:button>
        </form>
    </div>
</x-dynamic-component>
