<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Actions\ResendInvitationAction;
use IvanBaric\Velora\Actions\RevokeInvitationAction;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Http\Livewire\Concerns\InteractsWithActionResults;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\ActionResult;
use Livewire\Component;
use Livewire\WithPagination;

class TeamInvitationManager extends Component
{
    use InteractsWithActionResults;
    use WithPagination;

    protected $listeners = ['invitation-updated' => '$refresh'];

    public function resendInvitation(string $invitationUuid, ResendInvitationAction $resendInvitation): void
    {
        if (! $this->authorizeOrToast('manageMembers', team())) {
            return;
        }

        $this->ensureRateLimit('resend');

        $invitation = $this->resolveInvitationByUuid($invitationUuid);
        $this->ensureUserIsNotAlreadyMember($invitation->email);
        $result = $resendInvitation->execute($invitation, auth()->id(), $invitation->role_slug);
        $this->toastFromResult(ActionResult::success($result->message));
        $this->dispatch('invitation-updated');
    }

    public function revokeInvitation(string $invitationUuid, RevokeInvitationAction $revokeInvitation): void
    {
        if (! $this->authorizeOrToast('manageMembers', team())) {
            return;
        }

        $this->ensureRateLimit('revoke');

        $invitation = $this->resolveInvitationByUuid($invitationUuid);
        $result = $revokeInvitation->execute($invitation, auth()->id());
        if (! $result->success) {
            throw ValidationException::withMessages([
                'invitations' => $result->message,
            ]);
        }

        $this->toastFromResult($result);
        $this->dispatch('invitation-updated');
    }

    public function render(): View
    {
        TeamInvitation::query()
            ->pending()
            ->where('expires_at', '<=', now())
            ->get()
            ->each(fn (TeamInvitation $invitation) => $invitation->markExpired());

        return view('velora::livewire.team-invitation-manager', [
            'invitations' => TeamInvitation::query()
                ->with('inviter')
                ->orderByRaw(
                    "case when status = '".TeamInvitationStatus::Pending->value."' then 0 ".
                    "when status = '".TeamInvitationStatus::Expired->value."' then 1 ".
                    "when status = '".TeamInvitationStatus::Revoked->value."' then 2 ".
                    "when status = '".TeamInvitationStatus::Accepted->value."' then 3 else 4 end"
                )
                ->orderByDesc('last_sent_at')
                ->paginate(5),
            'roleLabels' => Role::query()
                ->availableToTeam(team()->getKey())
                ->pluck('name', 'slug')
                ->toArray(),
        ]);
    }

    protected function ensureUserIsNotAlreadyMember(string $email): void
    {
        $user = velora_user_query()->where('email', $email)->first();
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
                'invitations' => 'User is already a team member.',
            ]);
        }
    }

    protected function ensureRateLimit(string $action): void
    {
        $key = sprintf('velora:invitations:%s:%s:%s:%s', $action, (string) team()->getKey(), (string) auth()->id(), (string) request()->ip());

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'invitations' => "Too many invitation actions. Try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    protected function resolveInvitationByUuid(string $invitationUuid): TeamInvitation
    {
        /** @var TeamInvitation $invitation */
        $invitation = TeamInvitation::query()
            ->where('team_id', team()->getKey())
            ->where('uuid', $invitationUuid)
            ->firstOrFail();

        return $invitation;
    }
}
