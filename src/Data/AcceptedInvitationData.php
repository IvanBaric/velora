<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Data;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class AcceptedInvitationData
{
    public function __construct(
        public readonly Model $user,
        public readonly TeamInvitation $invitation,
        public readonly TeamMembership $membership,
        public readonly string $message,
    ) {}
}
