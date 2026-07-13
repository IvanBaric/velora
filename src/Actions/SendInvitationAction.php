<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Data\InvitationDispatchData;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Mail\TeamInvitationMail;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\TeamPermissions;

final class SendInvitationAction
{
    use AuthorizesActions;

    public function execute(string $email, string $roleSlug, int $teamId, ?int $actorUserId = null, bool $isOwner = false): InvitationDispatchData
    {
        $this->authorizeActionOrFail(TeamPermissions::MANAGE_MEMBERS, $teamId);

        if ($isOwner && ! $this->currentActorCanGrantOwnerAccess($teamId)) {
            throw ValidationException::withMessages([
                'email' => __('Samo vlasnik organizacije može poslati pozivnicu s vlasničkim pristupom.'),
            ]);
        }

        $normalizedEmail = TeamInvitation::normalizeEmail($email);
        $this->ensureUserIsNotAlreadyMember($normalizedEmail, $teamId);
        $this->ensureRoleExists($roleSlug, $teamId);

        [$invitation, $plainToken] = DB::transaction(function () use ($normalizedEmail, $roleSlug, $teamId, $actorUserId, $isOwner): array {
            $existing = TeamInvitation::query()
                ->where('team_id', $teamId)
                ->where('email', $normalizedEmail)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === TeamInvitationStatus::Pending && ! $existing->isExpired()) {
                throw ValidationException::withMessages([
                    'email' => __('Za ovaj email već postoji aktivna pozivnica.'),
                ]);
            }

            if ($existing && $existing->isExpired()) {
                $existing->markExpired($actorUserId);
            }

            if ($existing) {
                $plainToken = $existing->prepareForResend($actorUserId, $roleSlug);

                if (TeamInvitation::storesOwnerFlag()) {
                    $existing->forceFill(['is_owner' => $isOwner])->save();
                }

                return [$existing->fresh() ?? $existing, $plainToken];
            }

            $attributes = [
                'team_id' => $teamId,
                'email' => $normalizedEmail,
                'role_slug' => $roleSlug,
                'invited_by_user_id' => $actorUserId,
            ];

            if (TeamInvitation::storesOwnerFlag()) {
                $attributes['is_owner'] = $isOwner;
            }

            /** @var TeamInvitation $invitation */
            $invitation = TeamInvitation::query()->create($attributes);

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
            message: __('Pozivnica je poslana na :email.', ['email' => $invitation->email]),
        );
    }

    protected function ensureUserIsNotAlreadyMember(string $email, int $teamId): void
    {
        $normalizedEmail = TeamInvitation::normalizeEmail($email);
        $user = velora_user_query()
            ->whereRaw('lower(email) = ?', [$normalizedEmail])
            ->first();

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
                'email' => __('Suradnik je već član organizacije.'),
            ]);
        }
    }

    protected function ensureRoleExists(string $roleSlug, int $teamId): void
    {
        $exists = Role::query()
            ->availableToTeam($teamId)
            ->assignable()
            ->notHidden()
            ->where('slug', $roleSlug)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'roleSlug' => __('Odabranu ulogu nije moguće dodijeliti.'),
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

    private function currentActorCanGrantOwnerAccess(int $teamId): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $superadminAttribute = config('velora.access.superadmin_attribute');
        if (is_string($superadminAttribute) && $superadminAttribute !== '' && (bool) data_get($user, $superadminAttribute)) {
            return true;
        }

        return method_exists($user, 'ownsTeam') && $user->ownsTeam($teamId);
    }
}
