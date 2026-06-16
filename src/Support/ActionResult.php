<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use IvanBaric\Corexis\Data\ActionResult as CorexisActionResult;
use IvanBaric\Velora\Data\OperationResult;

/**
 * @deprecated Prefer IvanBaric\Corexis\Data\ActionResult for new Velora actions.
 */
final class ActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = [],
        public readonly ?string $code = null,
        public readonly array $errors = [],
    ) {}

    public static function success(string $message, mixed $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function error(string $message, ?string $code = null, mixed $data = [], array $errors = []): self
    {
        return new self(false, $message, $data, $code, $errors);
    }

    public static function fromOperationResult(OperationResult $result): self
    {
        return new self($result->ok, $result->message);
    }

    public static function fromCorexis(CorexisActionResult $result): self
    {
        return new self(
            success: $result->success,
            message: $result->message,
            data: $result->data,
            code: $result->code,
            errors: $result->errors,
        );
    }

    public function toCorexis(): CorexisActionResult
    {
        return new CorexisActionResult(
            success: $this->success,
            message: $this->message,
            data: $this->data,
            code: $this->code,
            errors: $this->errors,
        );
    }
}
