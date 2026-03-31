<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use Livewire\Component;

class TeamInvitationForm extends Component
{
    public string $email = '';

    public string $roleSlug = '';

    public function mount(): void
    {
        $this->roleSlug = TeamInvitation::defaultRoleSlug(team()->getKey()) ?? '';
    }

    public function validateInvitation(): bool
    {
        Gate::authorize('manageMembers', team());

        try {
            $this->validateInvitationData();

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return false;
        }
    }

    public function sendInvitation(): void
    {
        Gate::authorize('manageMembers', team());
        $this->ensureRateLimit('send');

        $normalizedEmail = $this->validateInvitationData();
        $existing = $this->findExistingInvitation($normalizedEmail);

        if ($existing) {
            $plainToken = $existing->prepareForResend(auth()->id(), $this->roleSlug);
            $invitation = $existing->fresh();
        } else {
            $invitation = TeamInvitation::query()->create([
                'email' => $normalizedEmail,
                'role_slug' => $this->roleSlug,
                'invited_by_user_id' => auth()->id(),
            ]);
            $plainToken = $invitation->issueToken();
        }

        $url = URL::temporarySignedRoute(
            'teams.invitation.accept',
            $invitation->expires_at ?? TeamInvitation::defaultExpiresAt(),
            ['token' => $plainToken],
        );

        $roleLabel = Role::query()
            ->availableToTeam(team()->getKey())
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug;

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation, $url, (string) $roleLabel));

        Flux::toast(variant: 'success', text: "Invitation sent to {$invitation->email}.");

        $this->reset('email');
        $this->dispatch('invitation-updated');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('velora::livewire.team-invitation-form', [
            'roles' => Role::query()->availableToTeam(team()->getKey())->assignable()->notHidden()->orderBy('sort_order')->get(),
        ]);
    }

    protected function validateInvitationData(): string
    {
        $normalizedEmail = TeamInvitation::normalizeEmail($this->email);

        Validator::make(
            ['email' => $normalizedEmail, 'role_slug' => $this->roleSlug],
            [
                'email' => ['required', 'email'],
                'role_slug' => ['required', 'string'],
            ],
        )->validate();

        $this->ensureUserIsNotAlreadyMember($normalizedEmail);
        $this->ensureRoleExists();

        return $normalizedEmail;
    }

    protected function ensureRoleExists(): void
    {
        $exists = Role::query()
            ->availableToTeam(team()->getKey())
            ->assignable()
            ->where('slug', $this->roleSlug)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'roleSlug' => 'Selected role is not assignable.',
            ]);
        }
    }

    protected function ensureUserIsNotAlreadyMember(string $normalizedEmail): void
    {
        $user = User::query()->where('email', $normalizedEmail)->first();
        if (! $user) {
            return;
        }

        $isMember = TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', team()->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();

        if ($isMember) {
            throw ValidationException::withMessages([
                'email' => 'User is already a team member.',
            ]);
        }
    }

    protected function ensureRateLimit(string $action): void
    {
        $key = sprintf('velora:invitations:%s:%s:%s:%s', $action, (string) team()->getKey(), (string) auth()->id(), (string) request()->ip());

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many invitation actions. Try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    protected function findExistingInvitation(string $normalizedEmail): ?TeamInvitation
    {
        $existing = TeamInvitation::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($existing?->status === TeamInvitationStatus::Pending && ! $existing->isExpired()) {
            throw ValidationException::withMessages([
                'email' => 'An active invitation already exists for this email.',
            ]);
        }

        if ($existing && $existing->isExpired()) {
            $existing->markExpired();
        }

        return $existing;
    }
}
