<?php

declare(strict_types=1);

use App\Models\User;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\TeamPermissions;

return [
    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Velora views can be overridden without publishing all package views.
    |
    | Add one or more absolute paths that will be searched first for the
    | "velora::" namespace. Example:
    |
    | - resources_path('views/velora')
    |
    | Then you can override only the file you need, e.g.:
    | resources/views/velora/invitations/accept.blade.php
    |
    */
    'views' => [
        'paths' => [
            // resource_path('views/velora'),
        ],
        'layouts' => [
            /*
            | Full-page Livewire layout used for Velora routed components
            | like /app/team and /app/team/create.
            */
            'app' => 'layouts.app',
        ],
        'components' => [
            /*
            |--------------------------------------------------------------
            | Component Overrides
            |--------------------------------------------------------------
            |
            | These let you swap Blade components used by Velora views
            | without overriding the views themselves.
            |
            | Example: 'auth_layout' => 'layouts.auth-split'
            | Then Velora will render: <x-layouts.auth-split>...</x-layouts.auth-split>
            */
            'auth_layout' => 'layouts.auth',
        ],
    ],

    'models' => [
        'user' => User::class,
        'team' => Team::class,
    ],

    'current_team' => [
        'strategy' => env('VELORA_CURRENT_TEAM_STRATEGY', 'strict'),
        'default_team_name' => env('VELORA_DEFAULT_TEAM_NAME', 'Default Team'),
        'system_team_name' => env('VELORA_SYSTEM_TEAM_NAME', 'System Team'),
    ],

    'session_key' => env('VELORA_CURRENT_TEAM_SESSION_KEY', 'velora.current_team_id'),

    'create_personal_team_on_registration' => env('VELORA_CREATE_PERSONAL_TEAM_ON_REGISTRATION', true),

    'sync_defaults_on_boot' => env('VELORA_SYNC_DEFAULTS_ON_BOOT', true),

    'roles' => [
        'hidden' => [
            'superadmin',
        ],
        'superadmin_slug' => 'superadmin',
        'default_member_slug' => 'member',
    ],

    'invitations' => [
        'expires_after_days' => (int) env('VELORA_INVITATION_EXPIRES_AFTER_DAYS', 7),
        'accept_redirect_route' => env('VELORA_INVITATION_ACCEPT_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'routes' => [
        'authenticated_middleware' => ['web', 'auth', 'set.team'],
        'public_middleware' => ['web'],
    ],

    'permissions' => [
        [
            'name' => 'Teams',
            'slug' => 'teams',
            'label' => 'Teams',
            'description' => 'Team and member management permissions.',
            'icon' => 'users',
            'sort_order' => 10,
            'items' => [
                ['name' => 'View', 'slug' => 'view', 'code' => 'teams.view', 'label' => 'View teams', 'sort_order' => 10],
                ['name' => 'Create', 'slug' => 'create', 'code' => 'teams.create', 'label' => 'Create teams', 'sort_order' => 20],
                ['name' => 'Update', 'slug' => 'update', 'code' => 'teams.update', 'label' => 'Update teams', 'sort_order' => 30],
                ['name' => 'Delete', 'slug' => 'delete', 'code' => 'teams.delete', 'label' => 'Delete teams', 'sort_order' => 40],
                ['name' => 'Manage Members', 'slug' => 'manage_members', 'code' => TeamPermissions::MANAGE_MEMBERS, 'label' => 'Manage members', 'sort_order' => 50],
                ['name' => 'Manage Roles', 'slug' => 'manage_roles', 'code' => TeamPermissions::MANAGE_ROLES, 'label' => 'Manage roles', 'sort_order' => 60],
            ],
        ],
        [
            'name' => 'Users',
            'slug' => 'users',
            'label' => 'Users',
            'description' => 'User management permissions.',
            'icon' => 'user-group',
            'sort_order' => 30,
            'items' => [
                ['name' => 'View', 'slug' => 'view', 'code' => 'users.view', 'label' => 'View users', 'sort_order' => 10],
                ['name' => 'Create', 'slug' => 'create', 'code' => 'users.create', 'label' => 'Create users', 'sort_order' => 20],
                ['name' => 'Update', 'slug' => 'update', 'code' => 'users.update', 'label' => 'Update users', 'sort_order' => 30],
                ['name' => 'Delete', 'slug' => 'delete', 'code' => 'users.delete', 'label' => 'Delete users', 'sort_order' => 40],
            ],
        ],
        [
            'name' => 'Settings',
            'slug' => 'settings',
            'label' => 'Settings',
            'description' => 'Settings permissions.',
            'icon' => 'cog-6-tooth',
            'sort_order' => 40,
            'items' => [
                ['name' => 'View', 'slug' => 'view', 'code' => 'settings.view', 'label' => 'View settings', 'sort_order' => 10],
                ['name' => 'Update', 'slug' => 'update', 'code' => 'settings.update', 'label' => 'Update settings', 'sort_order' => 20],
            ],
        ],
    ],

    'system_roles' => [
        [
            'name' => 'Superadmin',
            'slug' => 'superadmin',
            'label' => 'Superadmin',
            'description' => 'System role with full access.',
            'redirect_to' => '/dashboard',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => false,
            'is_active' => true,
            'sort_order' => 10,
            'all_permissions' => true,
        ],
        [
            'name' => 'Administrator',
            'slug' => 'admin',
            'label' => 'Administrator',
            'description' => 'System team admin role.',
            'redirect_to' => '/app/team',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 20,
            'permissions' => [
                TeamPermissions::MANAGE_MEMBERS,
                TeamPermissions::MANAGE_ROLES,
                'teams.view',
                'teams.update',
                'users.view',
                'users.create',
                'users.update',
                'settings.view',
                'settings.update',
            ],
        ],
        [
            'name' => 'Editor',
            'slug' => 'editor',
            'label' => 'Editor',
            'description' => 'System editor role.',
            'redirect_to' => '/dashboard',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 30,
            'permissions' => [
                'teams.view',
                'posts.create',
                'posts.update',
            ],
        ],
        [
            'name' => 'Member',
            'slug' => 'member',
            'label' => 'Member',
            'description' => 'Default member role.',
            'redirect_to' => '/dashboard',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 40,
            'permissions' => [
                'teams.view',
                'posts.view',
            ],
        ],
    ],
];
