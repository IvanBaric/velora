<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $attribute = config('velora.access.superadmin_attribute');

        abort_unless(
            is_string($attribute)
            && $attribute !== ''
            && (bool) data_get($request->user(), $attribute),
            403,
        );

        return $next($request);
    }
}
