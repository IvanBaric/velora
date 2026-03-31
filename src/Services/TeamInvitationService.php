<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Mail\TeamMemberJoinedMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\TeamPermissions;

final class TeamInvitationService
{
    /**
     * @return array<string, mixed>
     */
    public function previewData(string $token): array
    {
        $this->ensurePreviewRateLimit($token);

        $invitation = $this->resolve($token);
        $existingUser = $this->findExistingUser($invitation);

        return [
            'invitation' => $invitation,
            'token' => $token,
            'existingUser' => $existingUser,
            'roleLabel' => $this->resolveRoleLabel($invitation),
            'currentUser' => Auth::user(),
        ];
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $this->ensureSubmitRateLimit($request, $token);

        $invitation = $this->resolve($token);
        $existingUser = $this->findExistingUser($invitation);
        $currentUser = $this->currentUser();

        if ($currentUser && $currentUser->email !== $invitation->email) {
            Auth::logout();
        }

        if ($existingUser) {
            if ($currentUser && (int) $currentUser->getKey() === (int) $existingUser->getKey()) {
                return $this->acceptInvitation($existingUser, $invitation);
            }

            Validator::make($request->all(), [
                'password' => ['required', 'string'],
            ])->validate();

            if (! Hash::check((string) $request->string('password'), (string) $existingUser->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password is not correct.',
                ]);
            }

            Auth::login($existingUser, false);
            $request->session()->regenerate();

            return $this->acceptInvitation($existingUser, $invitation);
        }

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();

        /** @var User $user */
        $user = DB::transaction(function () use ($validated, $invitation): User {
            return User::query()->create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
            ]);
        });

        Auth::login($user, false);
        $request->session()->regenerate();

        return $this->acceptInvitation($user, $invitation);
    }

    public function resolve(string $token): TeamInvitation
    {
        /** @var TeamInvitation $invitation */
        $invitation = TeamInvitation::query()
            ->withoutGlobalScopes()
            ->forPlainToken($token)
            ->with(['team', 'inviter'])
            ->firstOrFail();

        if (! request()->hasValidSignature()) {
            if ($invitation->status === TeamInvitationStatus::Pending) {
                $invitation->markExpired();
            }

            abort(403, 'Invitation link has expired or is invalid.');
        }

        if ($invitation->status === TeamInvitationStatus::Revoked) {
            abort(403, 'This invitation has been revoked.');
        }

        if ($invitation->status === TeamInvitationStatus::Accepted) {
            abort(403, 'This invitation has already been used.');
        }

        if ($invitation->isExpired()) {
            $invitation->markExpired();
            abort(403, 'This invitation has expired.');
        }

        return $invitation;
    }

    private function ensurePreviewRateLimit(string $token): void
    {
        $rateKey = sprintf('velora:invitation:preview:%s:%s', hash('sha256', $token), (string) request()->ip());

        abort_if(RateLimiter::tooManyAttempts($rateKey, 60), 429, 'Too many invitation preview attempts.');

        RateLimiter::hit($rateKey, 60);
    }

    private function ensureSubmitRateLimit(Request $request, string $token): void
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

    private function acceptInvitation(User $user, TeamInvitation $invitation): RedirectResponse
    {
        $membership = TeamMembership::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'team_id' => $invitation->team_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => TeamMembershipStatus::Active,
                    'is_owner' => false,
                    'invited_by_user_id' => $invitation->invited_by_user_id,
                    'invited_email' => $invitation->email,
                    'joined_at' => now(),
                ],
            );

        if (! $membership->isActive()) {
            $membership->activate();
        }

        if ($invitation->role_slug) {
            $membership->syncRoles([$invitation->role_slug], $invitation->team_id);
        }

        $invitation->markAccepted((int) $user->getKey(), [
            'membership_id' => $membership->getKey(),
            'role_slug' => $invitation->role_slug,
        ]);

        $this->notifyAdmins($invitation, $user);

        set_current_team($invitation->team_id);

        return redirect()
            ->route('teams.settings')
            ->with('status', 'You joined team '.$invitation->team->name.'.');
    }

    private function notifyAdmins(TeamInvitation $invitation, User $user): void
    {
        TeamMembership::query()
            ->withoutGlobalScopes()
            ->with('user')
            ->where('team_id', $invitation->team_id)
            ->where('status', TeamMembershipStatus::Active->value)
            ->get()
            ->filter(function (TeamMembership $membership): bool {
                if (! $membership->user || ! $membership->user->email) {
                    return false;
                }

                return $membership->is_owner || $membership->hasPermissionTo(TeamPermissions::MANAGE_MEMBERS);
            })
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->each(fn (string $email) => Mail::to($email)->send(new TeamMemberJoinedMail($invitation, $user)));
    }

    private function findExistingUser(TeamInvitation $invitation): ?User
    {
        return User::query()
            ->where('email', $invitation->email)
            ->first();
    }

    private function resolveRoleLabel(TeamInvitation $invitation): ?string
    {
        return Role::query()
            ->withoutGlobalScopes()
            ->availableToTeam($invitation->team_id)
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug;
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
