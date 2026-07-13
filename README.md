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
- Optional entitlement integration through `VeloraEntitlements`.
- Optional Livewire + Flux UI for `/app/team`.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

Optional UI dependencies:

- `ivanbaric/admin-ui`
- `livewire/livewire`
- `livewire/flux`

If you only use the backend layer, you do not need Admin UI, Livewire, or Flux.

## Installation

Install the package:

```bash
composer require ivanbaric/velora
```

Install optional UI dependencies only if you want the packaged UI:

```bash
composer require ivanbaric/admin-ui livewire/livewire livewire/flux
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

Sync configured permissions and system roles after migrations, or after changing `config/velora.php`:

```bash
php artisan velora:sync
```

Default sync is production-safe: it creates missing permissions and system roles, but does not overwrite existing runtime labels, descriptions or role permissions.

Overwrite existing permissions and roles from config explicitly:

```bash
php artisan velora:sync --force
```

The `superadmin` role is not overwritten by `--force` unless `config('velora.sync.overwrite_superadmin')` is explicitly enabled.

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

### 2. Configure user and team models if needed

Velora resolves models through `config/velora.php`. If `velora.models.user` is not set, Velora falls back to Laravel's `auth.providers.users.model`. The default team model is `IvanBaric\Velora\Models\Team`.

```php
'models' => [
    'user' => App\Models\AdminUser::class,
    'team' => App\Models\Team::class,
],
```

Internally, Velora resolves the user model through `velora_user_model()`, `velora_user_query()`, and `velora_user_table()`.

Velora models expose UUID route keys through `IvanBaric\Velora\Traits\HasUuid`. That trait now delegates to the shared Corexis `HasUuid` concern, so UUID creation behavior stays aligned with the rest of the IvanBaric package ecosystem while the old Velora trait remains available for compatibility.

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

## Project Integration

For the standard admin integration used in every project, see:

- [`docs/project-integration.md`](docs/project-integration.md)

That document covers the required `Suradnici` sidebar link, Velora `teams.*` routes, route prefix/segment config, user model integration, permission sync and team-context middleware.

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

The package config keeps route middleware generic by default: `web`, `auth`, and `set.team`. Add app-specific middleware such as onboarding guards or domain guards in the host application's published config.

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
$user->membershipForTeam($team);

$user->ownsTeam($team);
$user->setTeamOwner($team);
$user->unsetTeamOwner($team);
```

Use `setTeamOwner()` and `unsetTeamOwner()` instead of writing `is_owner` directly from application code. The database flag remains an internal persistence detail.

### Roles and permissions

```php
$user->assignRole('admin');
$user->removeRole('admin');
$user->syncRoles(['member']); // zero or one role only

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

New Velora actions should return `IvanBaric\Corexis\Data\ActionResult` for action and domain outcomes that should not know about HTTP or UI.

`IvanBaric\Velora\Support\ActionResult` remains available as a backward-compatible adapter for existing consumers:

```php
ActionResult::success('Membership activated.');
ActionResult::error('Membership is already revoked.');
```

The compatibility class can convert to and from Corexis results with `toCorexis()` and `fromCorexis()`.

This keeps redirects in the HTTP layer and toast handling in the Livewire/UI layer.

## Domain Events

Velora domain events implement `IvanBaric\Corexis\Contracts\Events\DomainEvent` and Laravel's `ShouldDispatchAfterCommit` contract so cross-package listeners such as audit or notification integrations can react after successful writes.

## Authorization

Velora separates "who may act" from domain transitions:

- `TeamPolicy` answers authorization questions
- membership and invitation models/actions answer state-transition questions

### Permission overrides

Projects can override effective owner and role permissions without editing package code or changing stored role records:

```php
'authorization' => [
    'overrides' => [
        'owner' => [
            'teams.update' => false,
            'can_change_team_name' => false,
        ],
        'roles' => [
            'admin' => [
                'teams.manage_roles' => false,
            ],
        ],
    ],
],
```

Use permission codes such as `teams.update`, or common team aliases such as `can_change_team_name`, `can_create_team`, `can_delete_team`, `can_manage_members`, and `can_manage_roles`.

`false` denies a permission even when the member is an owner or the role has that permission. `true` grants a permission even when the role does not have a matching permission item. Missing keys fall back to normal Velora RBAC.

## Entitlements

Velora does not depend directly on a billing or plans package.

Plan-aware behavior is exposed through the `IvanBaric\Velora\Contracts\VeloraEntitlements` contract. Velora registers `IvanBaric\Velora\Support\AllowsAllVeloraEntitlements` by default, so a standalone Velora install allows team invitations and role management unless the host application binds a stricter implementation.

The contract controls:

- whether the current team can invite another member
- whether the current team can manage custom roles and permissions
- which plan code should be assigned when Velora creates a team with a `plan_code` column

To integrate another package or app-specific limits, bind the contract in a service provider:

```php
use IvanBaric\Velora\Contracts\VeloraEntitlements;

$this->app->singleton(VeloraEntitlements::class, App\Support\AppVeloraEntitlements::class);
```

When `ivanbaric/plans-entitlements` is installed, it can bind its own adapter for this contract and enforce its existing team member limits and `roles_and_permissions` feature flag. Velora only talks to the contract; the plans package owns the plan-specific rules.

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

The packaged views use `ivanbaric/admin-ui` layout primitives and Flux components. If you do not install Admin UI and Flux, the backend layer still works, but the packaged UI should not be used.

Example:

```blade
<livewire:teams.team-dropdown />
```

## Notes

- `sync_defaults_on_boot` is convenient in development. By default it is disabled in production unless you explicitly set `VELORA_SYNC_DEFAULTS_ON_BOOT=true`.
- If you replace the current-team resolver, bind your own implementation for `IvanBaric\Velora\Support\TeamContextResolver`.

## License

MIT
