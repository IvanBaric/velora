<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IvanBaric\Velora\Support\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function __construct(
        protected PermissionRegistrar $registrar,
    ) {}

    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($this->registrar->userHasRole($request->user(), $role), 403);

        return $next($request);
    }
}
