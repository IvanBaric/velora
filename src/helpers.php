<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\OrganizationModelResolver;
use IvanBaric\Velora\Support\TeamContextResolver;
use IvanBaric\Velora\Support\TeamModelResolver;
use IvanBaric\Velora\Support\UserModelResolver;

if (! function_exists('team')) {
    function team(): Model
    {
        if (app()->bound('team')) {
            /** @var Model $resolved */
            $resolved = app('team');

            return $resolved;
        }

        /** @var TeamContextResolver $resolver */
        $resolver = app(TeamContextResolver::class);
        $resolved = $resolver->resolve();

        app(TeamModelResolver::class)->bind($resolved);

        return $resolved;
    }
}

if (! function_exists('set_current_team')) {
    function set_current_team(Model|int|string $team): ?Model
    {
        /** @var TeamContextResolver $resolver */
        $resolver = app(TeamContextResolver::class);

        return $resolver->setCurrentTeam($team);
    }
}

if (! function_exists('velora_team_model')) {
    /**
     * @return class-string<Model>
     */
    function velora_team_model(): string
    {
        /** @var TeamModelResolver $resolver */
        $resolver = app(TeamModelResolver::class);

        return $resolver->className();
    }
}

if (! function_exists('velora_organization_model')) {
    /** @return class-string<Model> */
    function velora_organization_model(): string
    {
        return app(OrganizationModelResolver::class)->className();
    }
}

if (! function_exists('velora_organization_query')) {
    function velora_organization_query(): Builder
    {
        return app(OrganizationModelResolver::class)->query();
    }
}

if (! function_exists('velora_organization_table')) {
    function velora_organization_table(): string
    {
        return app(OrganizationModelResolver::class)->table();
    }
}

if (! function_exists('velora_team_query')) {
    function velora_team_query(): Builder
    {
        /** @var TeamModelResolver $resolver */
        $resolver = app(TeamModelResolver::class);

        return $resolver->query();
    }
}

if (! function_exists('velora_team_table')) {
    function velora_team_table(): string
    {
        /** @var TeamModelResolver $resolver */
        $resolver = app(TeamModelResolver::class);

        return $resolver->table();
    }
}

if (! function_exists('velora_is_team_model')) {
    function velora_is_team_model(mixed $value): bool
    {
        /** @var TeamModelResolver $resolver */
        $resolver = app(TeamModelResolver::class);

        return $resolver->isTeam($value);
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

if (! function_exists('velora_user_model')) {
    /**
     * @return class-string<Model>
     */
    function velora_user_model(): string
    {
        /** @var UserModelResolver $resolver */
        $resolver = app(UserModelResolver::class);

        return $resolver->className();
    }
}

if (! function_exists('velora_user_query')) {
    function velora_user_query(): Builder
    {
        /** @var UserModelResolver $resolver */
        $resolver = app(UserModelResolver::class);

        return $resolver->query();
    }
}

if (! function_exists('velora_user_table')) {
    function velora_user_table(): string
    {
        /** @var UserModelResolver $resolver */
        $resolver = app(UserModelResolver::class);

        return $resolver->table();
    }
}
