<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Exceptions;

use RuntimeException;

final class PlanFeatureUnavailableException extends RuntimeException
{
    public static function forFeature(string $feature): self
    {
        return new self(__('Feature [:feature] is not available on the current plan.', [
            'feature' => $feature,
        ]));
    }
}
