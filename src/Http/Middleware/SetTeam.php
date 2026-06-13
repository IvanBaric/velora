<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IvanBaric\Velora\Support\TeamContextResolver;
use IvanBaric\Velora\Support\TeamModelResolver;

class SetTeam
{
    public function __construct(
        protected TeamContextResolver $resolver,
        protected TeamModelResolver $teams,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $team = $this->resolver->resolve();

        $this->teams->bind($team);

        return $next($request);
    }
}
