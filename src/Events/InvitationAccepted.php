<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;

final class InvitationAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamInvitation $invitation,
        public TeamMembership $membership,
        public Model $user,
    ) {}
}
