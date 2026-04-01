<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Data;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Velora\Models\TeamInvitation;

final class InvitationPreviewData
{
    public function __construct(
        public readonly TeamInvitation $invitation,
        public readonly string $token,
        public readonly ?Model $existingUser,
        public readonly ?string $roleLabel,
        public readonly ?Model $currentUser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'invitation' => $this->invitation,
            'token' => $this->token,
            'existingUser' => $this->existingUser,
            'roleLabel' => $this->roleLabel,
            'currentUser' => $this->currentUser,
        ];
    }
}
