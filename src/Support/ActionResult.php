<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use IvanBaric\Velora\Data\OperationResult;

final class ActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function error(string $message): self
    {
        return new self(false, $message);
    }

    public static function fromOperationResult(OperationResult $result): self
    {
        return new self($result->ok, $result->message);
    }
}
