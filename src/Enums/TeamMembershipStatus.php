<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Enums;

enum TeamMembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'amber',
            self::Revoked => 'zinc',
        };
    }

    public function icon(): string
    {
        // Flux icon name (or any icon system consumer decides to map).
        return match ($this) {
            self::Active => 'check-circle',
            self::Suspended => 'pause-circle',
            self::Revoked => 'x-circle',
        };
    }

    public function tooltip(): string
    {
        return match ($this) {
            self::Active => 'Active member of the team.',
            self::Suspended => 'Temporarily disabled access.',
            self::Revoked => 'Access removed.',
        };
    }
}

