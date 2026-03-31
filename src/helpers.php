<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\TeamContextResolver;

if (! function_exists('team')) {
    function team(): Team
    {
        if (app()->bound('team')) {
            /** @var Team $resolved */
            $resolved = app('team');

            return $resolved;
        }

        /** @var TeamContextResolver $resolver */
        $resolver = app(TeamContextResolver::class);
        $resolved = $resolver->resolve();

        app()->instance('team', $resolved);
        app()->instance(Team::class, $resolved);

        return $resolved;
    }
}

if (! function_exists('set_current_team')) {
    function set_current_team(Team|int $team): ?Team
    {
        /** @var TeamContextResolver $resolver */
        $resolver = app(TeamContextResolver::class);

        return $resolver->setCurrentTeam($team);
    }
}

if (! function_exists('current_team_id')) {
    function current_team_id(): int
    {
        return (int) team()->getKey();
    }
}

if (! function_exists('membership')) {
    function membership(): ?TeamMembership
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'membershipForCurrentTeam')) {
            return null;
        }

        return $user->membershipForCurrentTeam();
    }
}

if (! function_exists('memberships')) {
    function memberships(): Collection
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'memberships')) {
            return collect();
        }

        return $user->memberships;
    }
}
