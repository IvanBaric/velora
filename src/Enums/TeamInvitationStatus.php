<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Enums;

enum TeamInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Accepted => 'green',
            self::Revoked => 'zinc',
            self::Expired => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Accepted => 'check-circle',
            self::Revoked => 'x-circle',
            self::Expired => 'alert-triangle',
        };
    }

    public function tooltip(): string
    {
        return match ($this) {
            self::Pending => 'Invitation is pending.',
            self::Accepted => 'Invitation was accepted.',
            self::Revoked => 'Invitation was revoked.',
            self::Expired => 'Invitation expired.',
        };
    }
}
