<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use IvanBaric\Velora\Data\InvitationPreviewData;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Exceptions\InvalidInvitation;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;

final class PreviewInvitationAction
{
    public function execute(string $token, bool $hasValidSignature, ?string $ipAddress = null): InvitationPreviewData
    {
        $this->ensureRateLimit($token, $ipAddress);

        $invitation = $this->resolveInvitation($token, $hasValidSignature);

        return new InvitationPreviewData(
            invitation: $invitation,
            token: $token,
            existingUser: $this->findExistingUser($invitation->email),
            roleLabel: $this->resolveRoleLabel($invitation),
            currentUser: Auth::user(),
        );
    }

    public function resolveInvitation(string $token, bool $hasValidSignature): TeamInvitation
    {
        /** @var TeamInvitation $invitation */
        $invitation = TeamInvitation::query()
            ->withoutGlobalScopes()
            ->forPlainToken($token)
            ->with(['team', 'inviter'])
            ->firstOrFail();

        if (! $hasValidSignature) {
            if ($invitation->status === TeamInvitationStatus::Pending) {
                $invitation->markExpired();
            }

            throw new InvalidInvitation('Invitation link has expired or is invalid.');
        }

        if ($invitation->status === TeamInvitationStatus::Revoked) {
            throw new InvalidInvitation('This invitation has been revoked.');
        }

        if ($invitation->status === TeamInvitationStatus::Accepted) {
            throw new InvalidInvitation('This invitation has already been used.');
        }

        if ($invitation->isExpired()) {
            $invitation->markExpired();

            throw new InvalidInvitation('This invitation has expired.');
        }

        return $invitation;
    }

    protected function ensureRateLimit(string $token, ?string $ipAddress): void
    {
        $rateKey = sprintf('velora:invitation:preview:%s:%s', hash('sha256', $token), (string) $ipAddress);

        if (RateLimiter::tooManyAttempts($rateKey, 60)) {
            throw new InvalidInvitation('Too many invitation preview attempts.', 429);
        }

        RateLimiter::hit($rateKey, 60);
    }

    protected function findExistingUser(string $email): ?Model
    {
        $user = velora_user_query()->where('email', $email)->first();

        return $user instanceof Model ? $user : null;
    }

    protected function resolveRoleLabel(TeamInvitation $invitation): ?string
    {
        return Role::query()
            ->withoutGlobalScopes()
            ->availableToTeam($invitation->team_id)
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug;
    }
}
