<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Exceptions;

use RuntimeException;

final class InvalidInvitation extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 403,
    ) {
        parent::__construct($message);
    }
}
