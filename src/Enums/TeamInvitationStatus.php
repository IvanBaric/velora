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
            self::Expired => 'exclamation-triangle',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Na čekanju'),
            self::Accepted => __('Prihvaćeno'),
            self::Revoked => __('Opozvano'),
            self::Expired => __('Isteklo'),
        };
    }

    public function tooltip(): string
    {
        return match ($this) {
            self::Pending => __('Pozivnica čeka odgovor.'),
            self::Accepted => __('Pozivnica je prihvaćena.'),
            self::Revoked => __('Pozivnica je opozvana.'),
            self::Expired => __('Pozivnica je istekla.'),
        };
    }
}
