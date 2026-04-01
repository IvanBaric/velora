<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Actions\AcceptInvitationAction;
use IvanBaric\Velora\Actions\CreateInvitedUserAction;
use IvanBaric\Velora\Actions\PreviewInvitationAction;
use IvanBaric\Velora\Data\AcceptedInvitationData;
use IvanBaric\Velora\Data\InvitationPreviewData;

final class TeamInvitationService
{
    public function __construct(
        private readonly PreviewInvitationAction $previewInvitation,
        private readonly CreateInvitedUserAction $createInvitedUser,
        private readonly AcceptInvitationAction $acceptInvitation,
    ) {}

    /**
     * Backwards-compatible adapter for existing consumers.
     *
     * @return array<string, mixed>
     */
    public function previewData(string $token): array
    {
        return $this->preview($token, request()->hasValidSignature(), request()->ip())->toViewData();
    }

    public function preview(string $token, bool $hasValidSignature, ?string $ipAddress = null): InvitationPreviewData
    {
        return $this->previewInvitation->execute($token, $hasValidSignature, $ipAddress);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $accepted = $this->acceptFromRequest($request, $token, $request->hasValidSignature());

        set_current_team($accepted->invitation->team_id);

        return redirect($this->acceptRedirectUrl())
            ->with('status', $accepted->message);
    }

    public function acceptFromRequest(Request $request, string $token, bool $hasValidSignature): AcceptedInvitationData
    {
        $this->ensureSubmitRateLimit($request, $token);

        $preview = $this->preview($token, $hasValidSignature, $request->ip());
        $invitation = $preview->invitation;
        $existingUser = $preview->existingUser;
        $currentUser = $this->currentUser();

        if ($currentUser && $currentUser->email !== $invitation->email) {
            Auth::logout();
            $currentUser = null;
        }

        if ($existingUser instanceof Model) {
            $user = $this->authenticateExistingUser($request, $existingUser, $currentUser);

            return $this->acceptInvitation->execute($user, $invitation);
        }

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();

        $user = $this->createInvitedUser->execute([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => $validated['password'],
        ]);

        Auth::login($user, false);
        $request->session()->regenerate();

        return $this->acceptInvitation->execute($user, $invitation);
    }

    protected function authenticateExistingUser(Request $request, Model $existingUser, ?Model $currentUser): Model
    {
        if ($currentUser && $currentUser->is($existingUser)) {
            return $existingUser;
        }

        Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ])->validate();

        if (! Hash::check((string) $request->string('password'), (string) $existingUser->getAttribute('password'))) {
            throw ValidationException::withMessages([
                'password' => 'Password is not correct.',
            ]);
        }

        Auth::login($existingUser, false);
        $request->session()->regenerate();

        return $existingUser;
    }

    protected function ensureSubmitRateLimit(Request $request, string $token): void
    {
        $rateKey = sprintf('velora:invitation:submit:%s:%s', hash('sha256', $token), (string) $request->ip());

        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($rateKey, 60);
    }

    protected function currentUser(): ?Model
    {
        $user = Auth::user();

        return $user instanceof Model ? $user : null;
    }

    protected function acceptRedirectUrl(): string
    {
        $routeName = (string) config('velora.invitations.accept_redirect_route', 'teams.settings');

        if ($routeName !== '' && app('router')->has($routeName)) {
            return route($routeName);
        }

        return '/';
    }
}
