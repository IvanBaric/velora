<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Listeners;

use Illuminate\Support\Facades\Mail;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Events\InvitationAccepted;
use IvanBaric\Velora\Mail\TeamMemberJoinedMail;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\TeamPermissions;

final class SendTeamMemberJoinedNotifications
{
    public function handle(InvitationAccepted $event): void
    {
        TeamMembership::query()
            ->withoutGlobalScopes()
            ->with('user')
            ->where('team_id', $event->invitation->team_id)
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
            ->each(fn (string $email) => Mail::to($email)->send(new TeamMemberJoinedMail($event->invitation, $event->user)));
    }
}
