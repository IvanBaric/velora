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
            self::Pending => 'green',
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
            self::Pending => 'Pozivnica čeka odgovor.',
            self::Accepted => 'Pozivnica je prihvaćena.',
            self::Revoked => 'Pozivnica je opozvana.',
            self::Expired => 'Pozivnica je istekla.',
        };
    }
}
