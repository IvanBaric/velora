# Velora Project Integration

This document describes the standard admin integration every project should add when it uses `ivanbaric/velora`.

Velora provides teams, memberships, invitations, roles, permissions and the optional packaged Livewire team UI. The host project owns the admin shell, sidebar label, app-specific middleware and any onboarding guards.

## Required Admin Link

Every project should expose the Velora team screen as `Suradnici`:

- `Suradnici` -> `route('teams.settings')`

Recommended sidebar item:

```blade
<flux:sidebar.item
    icon="users"
    :href="route('teams.settings')"
    :current="request()->routeIs('teams.*')"
    wire:navigate
>
    {{ __('Suradnici') }}
</flux:sidebar.item>
```

Use the `teams.*` route group for the active state because the user can move between team settings, team creation, team switching, role preview and invitation accept screens.

## Built-In Routes

Velora registers these routes from `packages/ivanbaric/velora/routes/web.php`.

Livewire routes are registered only when Livewire is installed:

```php
route('teams.settings'); // GET /app/team or /app/teams
route('teams.create');
route('teams.switch', ['team' => $team]);
route('teams.roles.preview', ['role' => $role]);
route('teams.roles.preview.stop');
```

Invitation routes are always registered:

```php
route('teams.invitation.accept', ['token' => $token]);
route('teams.invitation.accept.store', ['token' => $token]);
```

## Required Config

Configure the route prefix and segment in `config/velora.php`:

```php
'routes' => [
    'prefix' => env('VELORA_ROUTES_PREFIX', 'app'),
    'team_segment' => env('VELORA_ROUTES_TEAM_SEGMENT', 'teams'),
    'authenticated_middleware' => ['web', 'auth', 'set.team'],
    'public_middleware' => ['web'],
],
```

This gives the admin URL:

```text
/app/teams
```

If the project wants the singular URL from the package default, keep:

```php
'team_segment' => env('VELORA_ROUTES_TEAM_SEGMENT', 'team'),
```

which gives:

```text
/app/team
```

The route name stays `teams.settings` in both cases, so the sidebar link should use the route name instead of a hardcoded URL.

## Required User Integration

The project user model should use `HasVelora`:

```php
use IvanBaric\Velora\Traits\HasVelora;

class User extends Authenticatable
{
    use HasVelora;
}
```

If the project uses custom user or team models, set them explicitly:

```php
'models' => [
    'user' => App\Models\User::class,
    'team' => IvanBaric\Velora\Models\Team::class,
],
```

## Required Permissions

After migrations or permission changes, sync Velora defaults:

```bash
php artisan velora:sync
```

Use `--force` only when the project intentionally wants config values to overwrite runtime labels, descriptions or role permissions:

```bash
php artisan velora:sync --force
```

The `Suradnici` screen relies on these permission codes:

- `teams.view`
- `teams.create`
- `teams.update`
- `teams.delete`
- `teams.manage_members`
- `teams.manage_roles`

## Recommended Middleware

Use Velora team context on project routes that should be scoped to the current team:

```php
Route::middleware(['web', 'auth', 'set.team'])->group(function (): void {
    // Team-scoped project routes...
});
```

Projects can replace `set.team` with an app-specific wrapper middleware, but the wrapper should still resolve and bind the current Velora team.

## Recommended Redirects

Keep these redirects pointed at `teams.settings` unless the project has a dedicated post-invite or post-switch screen:

```php
'team_switch' => [
    'redirect_route' => env('VELORA_TEAM_SWITCH_REDIRECT_ROUTE', 'teams.settings'),
],

'team_settings' => [
    'leave_redirect_route' => env('VELORA_TEAM_SETTINGS_LEAVE_REDIRECT_ROUTE', 'teams.settings'),
],

'invitations' => [
    'accept_redirect_route' => env('VELORA_INVITATION_ACCEPT_REDIRECT_ROUTE', 'teams.settings'),
],
```

## Recommended Responsibility Split

Keep this responsibility split:

- `ivanbaric/velora` owns teams, memberships, invitations, role management, permissions and the optional team UI.
- The project owns the admin sidebar label `Suradnici`, app-specific middleware, onboarding redirects and visual placement inside the admin shell.
- Other packages and app modules should rely on Velora for team context instead of storing their own current-team logic.

