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
use IvanBaric\Velora\Models\TeamInvitation;

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
            ->with('status', $accepted->message)
            ->with('velora.toast', [
                'heading' => __('Uspješna registracija'),
                'text' => __('Uspješno ste registrirani i povezani s ustanovom.'),
                'variant' => 'success',
            ]);
    }

    public function acceptFromRequest(Request $request, string $token, bool $hasValidSignature): AcceptedInvitationData
    {
        $this->ensureSubmitRateLimit($request, $token);

        $preview = $this->preview($token, $hasValidSignature, $request->ip());
        $invitation = $preview->invitation;
        $existingUser = $preview->existingUser;
        $currentUser = $this->currentUser();

        if ($currentUser && TeamInvitation::normalizeEmail((string) $currentUser->email) !== $invitation->email) {
            Auth::logout();
            $currentUser = null;
        }

        if ($existingUser instanceof Model) {
            $user = $this->authenticateExistingUser($request, $existingUser, $currentUser);
            $accepted = $this->acceptInvitation->execute($user, $invitation);

            $this->loginAcceptedUser($request, $user);

            return $accepted;
        }

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], $this->invitedUserValidationMessages(), $this->invitedUserValidationAttributes())->validate();

        $accepted = $this->acceptInvitation->executeWithUserResolver(
            $invitation,
            fn (TeamInvitation $lockedInvitation): Model => $this->createInvitedUser->execute([
                'name' => $validated['name'],
                'email' => $lockedInvitation->email,
                'password' => $validated['password'],
            ]),
        );

        $this->loginAcceptedUser($request, $accepted->user);

        return $accepted;
    }

    protected function authenticateExistingUser(Request $request, Model $existingUser, ?Model $currentUser): Model
    {
        if ($currentUser && $currentUser->is($existingUser)) {
            return $existingUser;
        }

        Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ], $this->existingUserValidationMessages(), $this->invitedUserValidationAttributes())->validate();

        if (! Hash::check((string) $request->string('password'), (string) $existingUser->getAttribute('password'))) {
            throw ValidationException::withMessages([
                'password' => __('Lozinka nije točna.'),
            ]);
        }

        return $existingUser;
    }

    protected function loginAcceptedUser(Request $request, Model $user): void
    {
        $currentUser = $this->currentUser();
        if ($currentUser?->is($user)) {
            return;
        }

        Auth::login($user, false);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    protected function ensureSubmitRateLimit(Request $request, string $token): void
    {
        $rateKey = sprintf('velora:invitation:submit:%s:%s', hash('sha256', $token), (string) $request->ip());

        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);

            throw ValidationException::withMessages([
                'email' => __('Previše pokušaja. Pokušajte ponovno za :seconds sekundi.', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($rateKey, 60);
    }

    protected function currentUser(): ?Model
    {
        $user = Auth::user();

        return $user instanceof Model ? $user : null;
    }

    /** @return array<string, string> */
    protected function invitedUserValidationMessages(): array
    {
        return [
            'name.required' => __('Obavezno polje'),
            'name.string' => __('Ime i prezime mora biti tekst.'),
            'name.max' => __('Ime i prezime smije imati najviše :max znakova.'),
            'password.required' => __('Obavezno polje'),
            'password.confirmed' => __('Potvrda lozinke se ne podudara.'),
            'password.min' => __('Lozinka mora imati najmanje :min znakova.'),
            'password.letters' => __('Lozinka mora sadržavati barem jedno slovo.'),
            'password.mixed' => __('Lozinka mora sadržavati velika i mala slova.'),
            'password.numbers' => __('Lozinka mora sadržavati barem jedan broj.'),
            'password.symbols' => __('Lozinka mora sadržavati barem jedan simbol.'),
            'password.uncompromised' => __('Ova lozinka se pojavila u poznatom curenju podataka. Odaberite drugu lozinku.'),
        ];
    }

    /** @return array<string, string> */
    protected function existingUserValidationMessages(): array
    {
        return [
            'password.required' => __('Obavezno polje'),
            'password.string' => __('Lozinka mora biti tekst.'),
        ];
    }

    /** @return array<string, string> */
    protected function invitedUserValidationAttributes(): array
    {
        return [
            'name' => __('ime i prezime'),
            'password' => __('lozinka'),
            'password_confirmation' => __('potvrda lozinke'),
        ];
    }

    protected function acceptRedirectUrl(): string
    {
        $routeName = (string) config('velora.invitations.accept_redirect_route', 'dashboard');

        if ($routeName !== '' && app('router')->has($routeName)) {
            return route($routeName);
        }

        return '/';
    }
}
