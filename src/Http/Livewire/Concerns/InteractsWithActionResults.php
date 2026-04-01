<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire\Concerns;

use Flux\Flux;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use IvanBaric\Velora\Support\ActionResult;

trait InteractsWithActionResults
{
    protected function toastFromResult(ActionResult $result): void
    {
        if (! class_exists(Flux::class)) {
            return;
        }

        Flux::toast(
            variant: $result->success ? 'success' : 'danger',
            text: $result->message,
        );
    }

    protected function authorizeOrToast(string $ability, mixed $arguments = null): bool
    {
        $response = Gate::inspect($ability, $arguments);

        if ($response->allowed()) {
            return true;
        }

        $this->toastFromResult(ActionResult::error($this->authorizationMessage($response)));

        return false;
    }

    protected function authorizationMessage(Response $response): string
    {
        return $response->message() ?: 'You are not authorized to perform this action.';
    }
}
