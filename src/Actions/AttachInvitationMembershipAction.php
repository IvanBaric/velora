<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\PlanFeatures;
use IvanBaric\Velora\Support\TeamPlanUsage;

final class AttachInvitationMembershipAction
{
    public function execute(Model $user, TeamInvitation $invitation): TeamMembership
    {
        $alreadyActive = TeamMembership::query()
            ->withoutGlobalScopes()
            ->where('team_id', $invitation->team_id)
            ->where('user_id', $user->getKey())
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();

        if (! $alreadyActive && $invitation->team) {
            app(PlanAccess::class)->assertWithinLimit(
                $invitation->team,
                PlanFeatures::TEAM_MEMBERS_LIMIT,
                TeamPlanUsage::members($invitation->team),
            );
        }

        /** @var TeamMembership $membership */
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
            $membership->activate((int) $user->getKey());
        }

        if ($invitation->role_slug) {
            $result = $membership->syncRoles([$invitation->role_slug], $invitation->team_id, $invitation->invited_by_user_id);

            if (! $result->ok) {
                throw ValidationException::withMessages([
                    'email' => $result->message,
                ]);
            }
        }

        return $membership;
    }
}
