# Velora

Velora is a Laravel package for teams, memberships, invitations, and team-scoped RBAC.

It is designed as a backend-first package:

- The domain layer works without Livewire or Flux.
- The packaged Livewire UI is optional.
- Team context is explicit and configurable.
- Invitation and membership flows are split into reusable action classes.

## Features

- Teams and memberships with owner flag and status lifecycle.
- Invitation flow with signed URLs, token hashing, expiration, revoke/resend support, and accept flows for existing or new users.
- Team-scoped RBAC with global system roles plus team roles.
- One effective role per user per team.
- Membership transitions with audit history and events.
- Configurable current-team resolution strategy.
- Configurable user model via `velora.models.user`.
- Permission gate delegation, middleware aliases, and Blade directives.
- Optional Livewire + Flux UI for `/app/team`.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

Optional UI dependencies:

- `livewire/livewire`
- `livewire/flux`

If you only use the backend layer, you do not need Livewire or Flux.

## Installation

Install the package:

```bash
composer require ivanbaric/velora
```

Install optional UI dependencies only if you want the packaged UI:

```bash
composer require livewire/livewire livewire/flux
```

Run migrations:

```bash
php artisan migrate
```

Optional publish tags:

```bash
php artisan vendor:publish --tag=velora-config
php artisan vendor:publish --tag=velora-migrations
php artisan vendor:publish --tag=velora-views
```

## Quick Start

### 1. Add `HasVelora` to your user model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use IvanBaric\Velora\Traits\HasVelora;

class User extends Authenticatable
{
    use HasVelora;
}
```

### 2. Configure a custom user model if needed

By default the config points to `App\Models\User`. If your app uses a different model, set it in `config/velora.php`:

```php
'models' => [
    'user' => App\Models\AdminUser::class,
],
```

Internally, Velora resolves the user model through `velora_user_model()`, `velora_user_query()`, and `velora_user_table()`.

### 3. Resolve team context on app routes

Velora uses `set.team` on its own authenticated routes. Use the same middleware on your app routes that should run in team context:

```php
Route::middleware(['web', 'auth', 'set.team'])->group(function () {
    // Team-scoped routes...
});
```

### 4. Choose a current-team strategy

Velora defaults to strict team resolution:

```php
'current_team' => [
    'strategy' => env('VELORA_CURRENT_TEAM_STRATEGY', 'strict'),
],
```

Available strategies:

- `strict`
- `first_team`
- `create_default_team`
- `system_team_fallback`

If you do not want silent fallback behavior, keep `strict`.

## Built-in Routes

Livewire routes are only registered when Livewire is installed:

- `GET /app/team` -> `teams.settings`
- `GET /app/team/create` -> `teams.create`
- `GET /app/team/switch/{team}` -> `teams.switch`

Invitation routes are always registered:

- `GET /app/team/invitation/{token}` -> `teams.invitation.accept`
- `POST /app/team/invitation/{token}` -> `teams.invitation.accept.store`

## Configuration

Key options in `config/velora.php`:

- `velora.models.user`
- `velora.models.team`
- `velora.current_team.strategy`
- `velora.current_team.default_team_name`
- `velora.current_team.system_team_name`
- `velora.session_key`
- `velora.create_personal_team_on_registration`
- `velora.sync_defaults_on_boot`
- `velora.invitations.expires_after_days`
- `velora.invitations.accept_redirect_route`
- `velora.routes.authenticated_middleware`
- `velora.routes.public_middleware`
- `velora.views.layouts.app`
- `velora.views.components.auth_layout`

### View overrides

You can override Velora views without publishing everything:

```php
'views' => [
    'paths' => [
        resource_path('views/velora'),
    ],
],
```

Laravel's conventional fallback override path also works:

```text
resources/views/vendor/velora
```

## Current Team Context

Global helpers:

```php
team(): \IvanBaric\Velora\Models\Team
set_current_team(\IvanBaric\Velora\Models\Team|int $team): ?\IvanBaric\Velora\Models\Team
current_team_id(): int
membership(): ?\IvanBaric\Velora\Models\TeamMembership
memberships(): \Illuminate\Support\Collection
velora_user_model(): string
velora_user_query(): \Illuminate\Database\Eloquent\Builder
velora_user_table(): string
```

### Resolver behavior

`TeamContextResolver` first tries to resolve a preferred team:

1. Authenticated user's session-selected team, if membership is valid.
2. Authenticated user's active memberships.
3. Legacy `user->team_id`, if present.
4. Public session team id.

If no preferred team can be resolved, fallback behavior depends on `velora.current_team.strategy`:

- `strict` throws `UnableToResolveCurrentTeam`
- `first_team` uses the first persisted team
- `create_default_team` creates or loads the configured default team
- `system_team_fallback` returns a non-persisted in-memory team with `id = 0`

Velora does not silently create `Default Team` unless you explicitly choose `create_default_team`.

## Data Model

Core tables:

- `teams`
- `team_memberships`
- `team_membership_events`
- `team_invitations`
- `team_invitation_events`
- `roles`
- `user_roles`
- `permissions`
- `permission_items`
- `role_permission_items`

Core models:

- `IvanBaric\Velora\Models\Team`
- `IvanBaric\Velora\Models\TeamMembership`
- `IvanBaric\Velora\Models\TeamMembershipEvent`
- `IvanBaric\Velora\Models\TeamInvitation`
- `IvanBaric\Velora\Models\Role`
- `IvanBaric\Velora\Models\UserRole`

Most core records carry a UUID.

### Single role per team

Velora now enforces one role per `user_id + team_id`.

- `assignRole()` replaces the existing role for that team.
- `syncRoles()` is kept for backward compatibility, but only zero or one role is supported.
- The database migration enforces `unique(user_id, team_id)` on `user_roles`.
- The plural `roles()` relation still exists for compatibility, but the effective domain rule is single-role-per-team.

## User and Membership API

When your user model uses `HasVelora`, it gains membership and permission helpers.

### Memberships

```php
$user->memberships();
$user->teams();
$user->membershipForCurrentTeam();
```

### Roles and permissions

```php
$user->assignRole('admin');
$user->removeRole('admin');
$user->syncRoles(['editor']); // zero or one role only

$user->hasRole('admin');
$user->hasPermission('teams.manage_members');
```

`assignRole`, `removeRole`, and `syncRoles` return `IvanBaric\Velora\Data\OperationResult`.

### Membership transitions

`TeamMembership` has explicit state checks and transitions:

```php
$membership->canActivate();
$membership->canSuspend();
$membership->canRevoke();

$membership->activate($actorUserId);
$membership->suspend($actorUserId);
$membership->revoke($actorUserId);
```

Transition methods return `IvanBaric\Velora\Support\ActionResult`.

Successful membership transitions:

- update the membership state
- write to `team_membership_events`
- dispatch domain events

## Invitation Flow

Velora exposes a controller/service layer for HTTP and a dedicated action layer for reusable domain logic.

Primary invitation actions:

- `PreviewInvitationAction`
- `AcceptInvitationAction`
- `CreateInvitedUserAction`
- `AttachInvitationMembershipAction`
- `RevokeInvitationAction`
- `ResendInvitationAction`
- `SendInvitationAction`

Important behavior:

- invitation tokens are stored as SHA-256 hashes
- accept URLs are temporary signed routes
- expired and revoked invitations are blocked
- existing users can confirm password before accepting
- new users can be created from the accept flow
- accept redirect target is configurable through `velora.invitations.accept_redirect_route`

Reusable payload objects:

- `InvitationPreviewData`
- `AcceptedInvitationData`
- `InvitationDispatchData`

`TeamInvitationService` remains available as a backward-compatible adapter for existing consumers, while the domain logic lives in action classes.

## ActionResult Pattern

Velora uses `IvanBaric\Velora\Support\ActionResult` for action and domain outcomes that should not know about HTTP or UI:

```php
ActionResult::success('Membership activated.');
ActionResult::error('Membership is already revoked.');
```

This keeps redirects in the HTTP layer and toast handling in the Livewire/UI layer.

## Authorization

Velora separates "who may act" from domain transitions:

- `TeamPolicy` answers authorization questions
- membership and invitation models/actions answer state-transition questions

### Gate integration

Velora registers a gate delegate so permission codes can be used directly:

```php
Gate::authorize('teams.manage_members');
```

### Blade directives

```blade
@role('admin')
    ...
@endrole

@permission('teams.manage_members')
    ...
@endpermission
```

### Middleware aliases

- `set.team`
- `team.member`
- `role:{slug}`
- `permission:{code}`

Example:

```php
Route::middleware([
    'web',
    'auth',
    'set.team',
    'team.member',
    'permission:teams.manage_members',
])->group(function () {
    // ...
});
```

## Events

Velora dispatches events including:

- `TeamSwitched`
- `InvitationAccepted`
- `MembershipCreated`
- `MembershipActivated`
- `MembershipSuspended`
- `MembershipRevoked`
- `MembershipRoleSynced`
- `RoleAssigned`

You can move side effects like notifications and mail into listeners. Velora already uses a listener for join notifications after invitation acceptance.

## Optional Livewire UI

If Livewire is installed, Velora registers:

- `teams.team-settings`
- `teams.team-create`
- `teams.team-member-manager`
- `teams.team-invitation-form`
- `teams.team-invitation-manager`
- `teams.team-dropdown`
- `roles.role-manager`

The packaged views use Flux components. If you do not install Flux, the backend layer still works, but the packaged UI should not be used.

Example:

```blade
<livewire:teams.team-dropdown />
```

## Notes

- `sync_defaults_on_boot` is convenient in development, but some teams may prefer turning it off in production and managing seed/sync explicitly.
- If you replace the current-team resolver, bind your own implementation for `IvanBaric\Velora\Support\TeamContextResolver`.

## License

MIT
