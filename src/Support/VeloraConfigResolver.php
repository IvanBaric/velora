<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use IvanBaric\Corexis\Exceptions\InvalidConfiguration;
use IvanBaric\Corexis\Support\ConfigResolver;
use IvanBaric\Velora\Contracts\PlanAccess;
use IvanBaric\Velora\Models\Organization;
use IvanBaric\Velora\Models\Team;

final class VeloraConfigResolver
{
    /** @return class-string<Model> */
    public static function userModel(): string
    {
        $configured = config('velora.models.user');

        if ($configured !== null && $configured !== '') {
            return app(ConfigResolver::class)->model(
                key: 'velora.models.user',
                default: User::class,
                expectedType: Model::class,
            );
        }

        $default = config('auth.providers.users.model');

        if (! is_string($default) || $default === '') {
            throw InvalidConfiguration::invalidClass(
                key: 'velora.models.user or auth.providers.users.model',
                value: $configured,
                expectedType: Model::class,
            );
        }

        return app(ConfigResolver::class)->model(
            key: 'auth.providers.users.model',
            default: User::class,
            expectedType: Model::class,
        );
    }

    /** @return class-string<Model> */
    public static function teamModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'velora.models.team',
            default: Team::class,
            expectedType: Model::class,
        );
    }

    /** @return class-string<Model> */
    public static function organizationModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'velora.models.organization',
            default: Organization::class,
            expectedType: Organization::class,
        );
    }

    /** @return class-string<PlanAccess> */
    public static function planAccessResolver(): string
    {
        return app(ConfigResolver::class)->implementation(
            key: 'velora.plan_access.resolver',
            default: AllowAllPlanAccess::class,
            expectedType: PlanAccess::class,
        );
    }
}
