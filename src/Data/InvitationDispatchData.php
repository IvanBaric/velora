<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Data;

use IvanBaric\Velora\Models\TeamInvitation;

final class InvitationDispatchData
{
    public function __construct(
        public readonly TeamInvitation $invitation,
        public readonly string $plainToken,
        public readonly string $url,
        public readonly string $roleLabel,
        public readonly string $message,
    ) {}
}
