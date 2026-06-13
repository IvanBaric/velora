<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Data;

final readonly class VeloraEntitlementDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $message = null,
    ) {}

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(?string $message = null): self
    {
        return new self(false, $message);
    }
}
