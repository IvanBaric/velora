<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Exceptions;

use RuntimeException;

final class PlanLimitExceededException extends RuntimeException
{
    public static function forFeature(string $feature, int $limit, int $used): self
    {
        return new self(__('Feature [:feature] limit reached (:used/:limit).', [
            'feature' => $feature,
            'limit' => $limit,
            'used' => $used,
        ]));
    }
}
