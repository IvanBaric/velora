# Velora

> Note: Velora's built-in Livewire UI uses **Flux Pro** components. If you plan to use the included UI/views, you must have Flux Pro installed and configured. If you only use the backend (models, memberships, RBAC, middleware), Flux Pro is not required.

Velora is a Laravel package that provides **teams**, **memberships**, **invitations**, and **RBAC (roles + permissions)** with a single, consistent API and an optional Livewire UI.

It is designed around a **current team context** (`team()` / `set_current_team()`), and most team-scoped models are automatically filtered to the current team via a global scope.

## Features

- Team & membership models (`Team`, `TeamMembership`) with owner flag and status.
- Current team context resolver backed by session (with safe fallbacks).
- Invitation flow (`TeamInvitation`) with:
  - token hashing
  - temporary signed URLs
  - rate limiting
  - optional existing-user password confirmation
  - automatic membership creation and role assignment on accept
- RBAC:
  - `Role` (global system roles + team roles)
  - `Permission` groups and `PermissionItem` entries
  - `UserRole` assignments scoped to a team
  - helpers on the user/membership: `assignRole`, `syncRoles`, `hasRole`, `hasPermission`
- Authorization integration:
  - `Gate::before` delegate for permission codes like `teams.manage_members`
  - `@role(...)` and `@permission(...)` Blade directives
  - `TeamPolicy` for team update/member management rules
- Middleware:
  - `set.team` to resolve/bind the current team for the request
  - `team.member` to require membership in the current team
  - `role:<slug>` and `permission:<code>` helpers for route protection
- Livewire UI (Flux-based) for:
  - team settings (`/app/team`)
  - team create (`/app/team/create`)
  - member manager, invitations, team switch dropdown
  - role manager (team roles + permission selection)
- Default permissions and system roles sync on boot (config-driven, optional).

## Requirements

- PHP 8.2+
- Laravel 11/12/13
- Livewire 3/4
- Flux UI (used by included views/components)

## Installation

Install the package:

```bash
composer require ivanbaric/velora
```

Run migrations (after publishing or directly from the package):

```bash
php artisan migrate
```

Optional: publish config, migrations, and/or views:

```bash
php artisan vendor:publish --tag=velora-config
php artisan vendor:publish --tag=velora-migrations
php artisan vendor:publish --tag=velora-views
```

## Quick Start

### 1) Add traits to your User model

Add `HasVelora` to your user model (this provides memberships + roles/permissions API):

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

### 2) Ensure the current team is resolved for your app routes

Add the `set.team` middleware to routes that should run in a team context (Velora uses this by default for its own authenticated routes).

Example:

```php
Route::middleware(['web', 'auth', 'set.team'])->group(function () {
    // Your team-scoped routes...
});
```

### 3) Visit the built-in UI

Velora registers routes (by default):

- `GET /app/team` (team settings page)
- `GET /app/team/create` (create a new team)

Invitation accept routes:

- `GET /app/team/invitation/{token}` (signed link)
- `POST /app/team/invitation/{token}` (accept invitation)

## Configuration

The config file is `config/velora.php` (publishable via `velora-config`). Key options:

- `velora.models.user`: user model class used for relationships.
- `velora.models.team`: team model class.
- `velora.session_key`: session key for storing the current team id.
- `velora.routes.authenticated_middleware`: middleware stack used for Velora's authenticated routes.
- `velora.views.layouts.app`: layout used by Velora routed Livewire pages.
- `velora.views.components.auth_layout`: layout component used by the invitation accept view.
- `velora.create_personal_team_on_registration`: create a personal team on `Registered` event.
- `velora.sync_defaults_on_boot`: sync permissions + system roles from config into the database.

### Views overrides (without publishing everything)

You can add override paths for the `velora::` namespace:

```php
// config/velora.php
'views' => [
    'paths' => [
        resource_path('views/velora'),
    ],
],
```

Then override a single file, for example:

`resources/views/velora/livewire/team-settings.blade.php`

## Current Team Context

Velora exposes a few global helper functions (loaded by the service provider):

```php
team(): \IvanBaric\Velora\Models\Team
set_current_team(\IvanBaric\Velora\Models\Team|int $team): ?\IvanBaric\Velora\Models\Team
current_team_id(): int
membership(): ?\IvanBaric\Velora\Models\TeamMembership
memberships(): \Illuminate\Support\Collection
```

### How `team()` is resolved

`team()` uses `TeamContextResolver` with this strategy:

1. For an authenticated user: resolve the team from a session-stored team id (if the user is a member).
2. Otherwise: resolve the first active membership team for the user.
3. Otherwise: resolve from a public session team id.
4. Otherwise: use the first team in the database, or create a `Default Team` if none exist.
5. If anything fails (e.g. early boot before migrations): return a non-persisted `System Team` with `id = 0`.

### Team-scoped global scopes

Models using `IvanBaric\Velora\Traits\BelongsToTeam` automatically:

- set `team_id` on create (from `team()`), if not provided
- add a global scope that filters `team_id = team()->id`

If you need cross-team queries:

```php
TeamMembership::query()
    ->withoutGlobalScopes()
    ->where('user_id', $userId)
    ->get();
```

## Data Model Overview

Core tables (migrations provided):

- `teams`
- `team_memberships`
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
- `IvanBaric\Velora\Models\TeamInvitation`
- `IvanBaric\Velora\Models\Role`
- `IvanBaric\Velora\Models\Permission` / `PermissionItem`
- `IvanBaric\Velora\Models\UserRole`

Most models include a `uuid` column and automatically populate it via `HasUuid`.

## User API (Memberships, Roles, Permissions)

When your `User` uses `HasVelora`, it gains:

### Memberships

```php
$user->memberships();              // hasMany TeamMembership
$user->teams();                    // belongsToMany Team via team_memberships
$user->membershipForCurrentTeam(); // TeamMembership|null
```

### Roles and permissions

```php
// Assign / remove / sync (team-scoped)
$user->assignRole('admin');          // returns OperationResult
$user->removeRole('admin');
$user->syncRoles(['editor', 'member']);

// Checks
$user->hasRole('admin');
$user->hasPermission('teams.manage_members');
```

`assignRole`, `removeRole`, and `syncRoles` return `IvanBaric\Velora\Data\OperationResult`.

### Membership API

`TeamMembership` also uses `HasTeamRolesPermissions`, so you can do:

```php
$membership = membership();

if ($membership?->hasPermissionTo('teams.manage_members')) {
    // ...
}
```

## Authorization Integration

### Gates

Velora registers:

- `Gate::before(...)` that delegates abilities containing a dot (`.`) to `user->hasPermission($ability, $team)`
- A policy for `Team::class` (`TeamPolicy`)

That means you can use permission codes directly as gate abilities:

```php
Gate::authorize('teams.manage_members');
```

### Blade directives

Velora registers:

```blade
@role('admin')
    ...
@endrole

@permission('teams.manage_members')
    ...
@endpermission
```

## Middleware

Velora registers middleware aliases:

- `set.team` → resolves and binds the current team into the container
- `team.member` → requires active membership in the current team
- `role:<slug>` → requires a role in current team context
- `permission:<code>` → requires a permission in current team context

Example:

```php
Route::middleware(['web', 'auth', 'set.team', 'team.member', 'permission:teams.manage_members'])->group(function () {
    // ...
});
```

## Invitations

Velora provides a full invitation UI + routes out of the box (Livewire + mail).

Under the hood:

- Invitations store a `token_hash` (SHA-256 hash of the plain token).
- Accept URLs are **temporary signed routes**.
- Accepting an invitation:
  - creates (or activates) a `TeamMembership`
  - optionally assigns roles based on `role_slug`
  - sets the current team (`set_current_team(...)`)
  - emails admins/owners that a member joined

Mailables:

- `IvanBaric\Velora\Mail\TeamInvitationMail`
- `IvanBaric\Velora\Mail\TeamMemberJoinedMail`

## Livewire Components

Registered components (if Livewire is installed):

- `teams.team-settings`
- `teams.team-create`
- `teams.team-member-manager`
- `teams.team-invitation-form`
- `teams.team-invitation-manager`
- `teams.team-dropdown`
- `roles.role-manager`

Example usage:

```blade
<livewire:teams.team-dropdown />
```

## Events

Velora dispatches:

- `IvanBaric\Velora\Events\TeamSwitched` (when `set_current_team(...)` is called)
- `IvanBaric\Velora\Events\MembershipCreated` (when a membership is first created)
- `IvanBaric\Velora\Events\RoleAssigned` (when `assignRole(...)` assigns a role)

## Notes / Customization

- If you want full control over how the current team is resolved, bind your own resolver into the container in your app service provider (replacing `TeamContextResolver`).
- Some parts of the package use `App\\Models\\User` directly (routes, certain Livewire components, policy, and a mailable). If you plan to use a different user model class, adjust those references accordingly.

## License

MIT
