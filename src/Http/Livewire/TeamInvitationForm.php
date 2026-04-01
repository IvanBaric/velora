<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Actions\SendInvitationAction;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\ActionResult;
use Livewire\Component;

class TeamInvitationForm extends Component
{
    use InteractsWithActionResults;

    public string $email = '';

    public string $roleSlug = '';

    public function mount(): void
    {
        $this->roleSlug = TeamInvitation::defaultRoleSlug(team()->getKey()) ?? '';
    }

    public function validateInvitation(): bool
    {
        if (! $this->authorizeOrToast('manageMembers', team())) {
            return false;
        }

        try {
            $this->validateInvitationData();

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return false;
        }
    }

    public function sendInvitation(SendInvitationAction $sendInvitation): void
    {
        if (! $this->authorizeOrToast('manageMembers', team())) {
            return;
        }

        $this->ensureRateLimit('send');

        $normalizedEmail = $this->validateInvitationData();

        $result = $sendInvitation->execute($normalizedEmail, $this->roleSlug, current_team_id(), auth()->id());
        $this->toastFromResult(ActionResult::success($result->message));

        $this->reset('email');
        $this->dispatch('invitation-updated');
    }

    public function render(): View
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

        return $normalizedEmail;
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
}
