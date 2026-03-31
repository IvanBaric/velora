<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use Livewire\Component;
use Livewire\WithPagination;

class TeamInvitationManager extends Component
{
    use WithPagination;

    protected $listeners = ['invitation-updated' => '$refresh'];

    public function resendInvitation(string $invitationUuid): void
    {
        Gate::authorize('manageMembers', team());
        $this->ensureRateLimit('resend');

        $invitation = $this->resolveInvitationByUuid($invitationUuid);
        $this->ensureUserIsNotAlreadyMember($invitation->email);

        $plainToken = $invitation->prepareForResend(auth()->id(), $invitation->role_slug);

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

        Flux::toast(variant: 'success', text: "Invitation resent to {$invitation->email}.");
        $this->dispatch('invitation-updated');
    }

    public function revokeInvitation(string $invitationUuid): void
    {
        Gate::authorize('manageMembers', team());
        $this->ensureRateLimit('revoke');

        $invitation = $this->resolveInvitationByUuid($invitationUuid);
        if ($invitation->status === TeamInvitationStatus::Accepted) {
            throw ValidationException::withMessages([
                'invitations' => 'Accepted invitations cannot be revoked.',
            ]);
        }

        $invitation->markRevoked(auth()->id(), ['reason' => 'manual_revoke']);
        Flux::toast(variant: 'success', text: 'Invitation revoked.');
        $this->dispatch('invitation-updated');
    }

    public function render(): \Illuminate\Contracts\View\View
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
        $user = User::query()->where('email', $email)->first();
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
            ->where('uuid', $invitationUuid)
            ->firstOrFail();

        return $invitation;
    }
}
