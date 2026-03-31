<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use Symfony\Component\HttpFoundation\Response;

class VerifyMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'memberships')) {
            return $next($request);
        }

        $isMember = $user->memberships()
            ->where('team_id', team()->getKey())
            ->where('status', TeamMembershipStatus::Active->value)
            ->exists();

        abort_unless($isMember, 403, 'You are not a member of this team.');

        return $next($request);
    }
}
