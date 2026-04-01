<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Data;

final readonly class OperationResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public bool $ok,
        public string $message,
        public string $code = 'ok',
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(string $message, array $data = [], string $code = 'ok'): self
    {
        return new self(true, $message, $code, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function failure(string $message, array $data = [], string $code = 'error'): self
    {
        return new self(false, $message, $code, $data);
    }

    /**
     * @return array{ok: bool, message: string, code: string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
            'code' => $this->code,
            'data' => $this->data,
        ];
    }
}
