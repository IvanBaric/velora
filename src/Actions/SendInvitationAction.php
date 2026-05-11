<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Data\InvitationDispatchData;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class SendInvitationAction
{
    public function execute(string $email, string $roleSlug, int $teamId, ?int $actorUserId = null): InvitationDispatchData
    {
        $normalizedEmail = TeamInvitation::normalizeEmail($email);
        $this->ensureUserIsNotAlreadyMember($normalizedEmail, $teamId);
        $this->ensureRoleExists($roleSlug, $teamId);

        [$invitation, $plainToken] = DB::transaction(function () use ($normalizedEmail, $roleSlug, $teamId, $actorUserId): array {
            $existing = TeamInvitation::query()
                ->where('team_id', $teamId)
                ->where('email', $normalizedEmail)
                ->first();

            if ($existing?->status === TeamInvitationStatus::Pending && ! $existing->isExpired()) {
                throw ValidationException::withMessages([
                    'email' => 'Za ovaj email već postoji aktivna pozivnica.',
                ]);
            }

            if ($existing && $existing->isExpired()) {
                $existing->markExpired($actorUserId);
            }

            if ($existing) {
                $plainToken = $existing->prepareForResend($actorUserId, $roleSlug);

                return [$existing->fresh() ?? $existing, $plainToken];
            }

            /** @var TeamInvitation $invitation */
            $invitation = TeamInvitation::query()->create([
                'team_id' => $teamId,
                'email' => $normalizedEmail,
                'role_slug' => $roleSlug,
                'invited_by_user_id' => $actorUserId,
            ]);

            $plainToken = $invitation->issueToken();

            return [$invitation->fresh() ?? $invitation, $plainToken];
        });

        $roleLabel = $this->resolveRoleLabel($invitation, $teamId);
        $url = URL::temporarySignedRoute(
            'teams.invitation.accept',
            $invitation->expires_at ?? TeamInvitation::defaultExpiresAt(),
            ['token' => $plainToken],
        );

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation, $url, $roleLabel));

        return new InvitationDispatchData(
            invitation: $invitation,
            plainToken: $plainToken,
            url: $url,
            roleLabel: $roleLabel,
            message: 'Pozivnica je poslana na '.$invitation->email.'.',
        );
    }

    protected function ensureUserIsNotAlreadyMember(string $email, int $teamId): void
    {
        $user = velora_user_query()->where('email', $email)->first();
        if (! $user) {
            return;
        }

        $isMember = TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', $teamId)
            ->where('user_id', $user->getKey())
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();

        if ($isMember) {
            throw ValidationException::withMessages([
                'email' => 'Korisnik je već član tima.',
            ]);
        }
    }

    protected function ensureRoleExists(string $roleSlug, int $teamId): void
    {
        $exists = Role::query()
            ->availableToTeam($teamId)
            ->assignable()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'roleSlug' => 'Odabranu ulogu nije moguće dodijeliti.',
            ]);
        }
    }

    protected function resolveRoleLabel(TeamInvitation $invitation, int $teamId): string
    {
        return (string) (Role::query()
            ->availableToTeam($teamId)
            ->where('slug', $invitation->role_slug)
            ->value('name') ?? $invitation->role_slug);
    }
}
