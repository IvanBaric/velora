<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Actions\SendInvitationAction;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Exceptions\PlanFeatureUnavailableException;
use IvanBaric\Velora\Exceptions\PlanLimitExceededException;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Support\ActionResult;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPlanUsage;
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
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return false;
        }

        return $this->canInviteWithinPlan();
    }

    public function sendInvitation(SendInvitationAction $sendInvitation): void
    {
        if (! $this->authorizeOrToast('manageMembers', team())) {
            return;
        }

        $this->ensureRateLimit('send');

        $normalizedEmail = $this->validateInvitationData();

        if (! $this->canInviteWithinPlan()) {
            return;
        }

        $result = $sendInvitation->execute($normalizedEmail, $this->roleSlug, current_team_id(), auth()->id());
        $this->toastFromResult(ActionResult::success($result->message));

        $this->reset('email');
        $this->dispatch('invitation-updated');
    }

    public function render(): View
    {
        $invitationBlockedMessage = $this->invitationBlockedMessage();

        return view('velora::livewire.team-invitation-form', [
            'roles' => Role::query()->availableToTeam(team()->getKey())->assignable()->notHidden()->orderBy('sort_order')->get(),
            'canInviteWithinCurrentPlan' => $invitationBlockedMessage === null,
            'invitationBlockedMessage' => $invitationBlockedMessage,
        ]);
    }

    protected function validateInvitationData(): string
    {
        $normalizedEmail = TeamInvitation::normalizeEmail($this->email);

        Validator::make(
            ['email' => $normalizedEmail, 'role_slug' => $this->roleSlug],
            [
                'email' => ['required', 'email', 'max:255'],
                'role_slug' => [
                    'required',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $exists = Role::query()
                            ->availableToTeam(team()->getKey())
                            ->assignable()
                            ->notHidden()
                            ->where('slug', (string) $value)
                            ->exists();

                        if (! $exists) {
                            $fail(__('Odabranu ulogu nije moguće dodijeliti.'));
                        }
                    },
                ],
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
                'email' => __('Previše akcija s pozivnicama. Pokušajte ponovno za :seconds sekundi.', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    protected function canInviteWithinPlan(): bool
    {
        try {
            app(PlanAccess::class)->assertWithinLimit(
                team(),
                PlanFeatures::TEAM_MEMBERS_LIMIT,
                TeamPlanUsage::members(team()),
            );

            return true;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException $exception) {
            $this->toastFromResult(ActionResult::error(
                $this->invitationBlockedMessage() ?? trim($exception->getMessage().' '.__('Existing team members keep access, but your current plan cannot add more. Upgrade your plan to continue.'))
            ));

            return false;
        }
    }

    protected function invitationBlockedMessage(): ?string
    {
        try {
            app(PlanAccess::class)->assertWithinLimit(
                team(),
                PlanFeatures::TEAM_MEMBERS_LIMIT,
                TeamPlanUsage::members(team()),
            );

            return null;
        } catch (PlanLimitExceededException|PlanFeatureUnavailableException) {
            $planCode = (string) (team()->plan_code ?: 'starter');
            $planName = __("plans::plans.{$planCode}.name");

            return __('Invitations are not available on the :plan plan because the team member limit has been reached. Upgrade your plan to add collaborators.', [
                'plan' => $planName,
            ]);
        }
    }
}
