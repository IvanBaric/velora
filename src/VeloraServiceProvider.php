<?php

declare(strict_types=1);

namespace IvanBaric\Velora;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use IvanBaric\Velora\Actions\CreatePersonalTeam;
use IvanBaric\Velora\Events\InvitationAccepted;
use IvanBaric\Velora\Http\Livewire\RoleManager;
use IvanBaric\Velora\Http\Livewire\TeamCreate;
use IvanBaric\Velora\Http\Livewire\TeamDropdown;
use IvanBaric\Velora\Http\Livewire\TeamInvitationForm;
use IvanBaric\Velora\Http\Livewire\TeamInvitationManager;
use IvanBaric\Velora\Http\Livewire\TeamMemberManager;
use IvanBaric\Velora\Http\Livewire\TeamSettings;
use IvanBaric\Velora\Http\Middleware\EnsurePermission;
use IvanBaric\Velora\Http\Middleware\EnsureRole;
use IvanBaric\Velora\Http\Middleware\SetTeam;
use IvanBaric\Velora\Http\Middleware\VerifyMembership;
use IvanBaric\Velora\Listeners\SendTeamMemberJoinedNotifications;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Policies\TeamPolicy;
use IvanBaric\Velora\Support\PermissionRegistrar;
use IvanBaric\Velora\Support\SystemAccessSynchronizer;
use IvanBaric\Velora\Support\TeamContextResolver;
use IvanBaric\Velora\Support\UserModelResolver;
use Livewire\Livewire;

class VeloraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/velora.php', 'velora');

        $this->app->singleton(TeamContextResolver::class);
        $this->app->singleton(PermissionRegistrar::class);
        $this->app->singleton(SystemAccessSynchronizer::class);
        $this->app->singleton(UserModelResolver::class);

        $helpers = __DIR__.'/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $viewOverridePaths = array_values(array_filter((array) config('velora.views.paths', []), fn ($path) => is_string($path) && $path !== ''));

        // Keep the conventional Laravel override directory as a fallback even when config isn't published.
        $this->loadViewsFrom(
            array_values(array_unique([
                ...$viewOverridePaths,
                resource_path('views/vendor/velora'),
                __DIR__.'/../resources/views',
            ])),
            'velora'
        );
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Team::class, TeamPolicy::class);

        $this->registerGates();
        $this->registerMiddleware();
        $this->registerLivewire();
        $this->registerEvents();
        $this->syncDefaults();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/velora.php' => config_path('velora.php'),
        ], 'velora-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'velora-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/velora'),
        ], 'velora-views');
    }

    protected function registerGates(): void
    {
        Gate::before(function ($user, string $ability, array $arguments = []): ?bool {
            return $this->app->make(PermissionRegistrar::class)->userCan($user, $ability, $arguments);
        });

        Blade::if('role', fn (string $role): bool => $this->app->make(PermissionRegistrar::class)->userHasRole(auth()->user(), $role));
        Blade::if('permission', fn (string $permission): bool => (bool) auth()->user()?->hasPermission($permission));
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('set.team', SetTeam::class);
        $router->aliasMiddleware('team.member', VerifyMembership::class);
        $router->aliasMiddleware('role', EnsureRole::class);
        $router->aliasMiddleware('permission', EnsurePermission::class);
    }

    protected function registerLivewire(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('roles.role-manager', RoleManager::class);
        Livewire::component('teams.team-settings', TeamSettings::class);
        Livewire::component('teams.team-create', TeamCreate::class);
        Livewire::component('teams.team-member-manager', TeamMemberManager::class);
        Livewire::component('teams.team-invitation-form', TeamInvitationForm::class);
        Livewire::component('teams.team-invitation-manager', TeamInvitationManager::class);
        Livewire::component('teams.team-dropdown', TeamDropdown::class);
    }

    protected function registerEvents(): void
    {
        Event::listen(InvitationAccepted::class, SendTeamMemberJoinedNotifications::class);

        if (config('velora.create_personal_team_on_registration', true)) {
            Event::listen(Registered::class, CreatePersonalTeam::class);
        }
    }

    protected function syncDefaults(): void
    {
        if (! config('velora.sync_defaults_on_boot', true)) {
            return;
        }

        try {
            $this->app->make(SystemAccessSynchronizer::class)->sync();
        } catch (\Throwable) {
            // Early boot before migrations is expected in some commands.
        }
    }
}
