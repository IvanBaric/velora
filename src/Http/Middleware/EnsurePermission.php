<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $teamId = (int) ($user?->current_team_id ?: $user?->team_id ?: 0);

        abort_unless($user && $user->hasPermission($permission, $teamId ?: null), 403);

        return $next($request);
    }
}
