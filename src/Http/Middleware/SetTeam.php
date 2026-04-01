<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\TeamContextResolver;

class SetTeam
{
    public function __construct(
        protected TeamContextResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $team = $this->resolver->resolve();

        app()->instance('team', $team);
        app()->instance(Team::class, $team);

        return $next($request);
    }
}
