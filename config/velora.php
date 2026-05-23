<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
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
        'default_team_name' => env('VELORA_DEFAULT_TEAM_NAME', 'Zadani tim'),
        'system_team_name' => env('VELORA_SYSTEM_TEAM_NAME', 'Sistemski tim'),
    ],

    'create_personal_team_on_registration' => env('VELORA_CREATE_PERSONAL_TEAM_ON_REGISTRATION', true),

    'create_personal_team_when_missing' => env('VELORA_CREATE_PERSONAL_TEAM_WHEN_MISSING', true),

    'sync_defaults_on_boot' => env('VELORA_SYNC_DEFAULTS_ON_BOOT', env('APP_ENV') !== 'production'),

    'roles' => [
        'hidden' => [
            'superadmin',
        ],
        'superadmin_slug' => 'superadmin',
        'default_member_slug' => 'member',
    ],

    'role_preview' => [
        'redirect_route' => env('VELORA_ROLE_PREVIEW_REDIRECT_ROUTE', 'app.dashboard'),
        'exit_redirect_route' => env('VELORA_ROLE_PREVIEW_EXIT_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'team_switch' => [
        'redirect_route' => env('VELORA_TEAM_SWITCH_REDIRECT_ROUTE', 'app.dashboard'),
    ],

    'invitations' => [
        'expires_after_days' => (int) env('VELORA_INVITATION_EXPIRES_AFTER_DAYS', 7),
        'accept_redirect_route' => env('VELORA_INVITATION_ACCEPT_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'routes' => [
        'prefix' => env('VELORA_ROUTES_PREFIX', 'app'),
        'team_segment' => env('VELORA_ROUTES_TEAM_SEGMENT', 'teams'),
        'authenticated_middleware' => ['web', 'auth', 'onboarding_guard', \App\Http\Middleware\EnsureMainDomain::class, 'set.team'],
        'public_middleware' => ['web'],
    ],

    'permissions' => [
        [
            'name' => 'Timovi',
            'slug' => 'teams',
            'label' => 'Timovi',
            'description' => 'Upravljanje timovima, članovima i ulogama.',
            'icon' => 'users',
            'sort_order' => 10,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'teams.view', 'label' => 'Pregled timova', 'sort_order' => 10],
                ['name' => 'Kreiranje', 'slug' => 'create', 'code' => 'teams.create', 'label' => 'Kreiranje timova', 'sort_order' => 20],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'teams.update', 'label' => 'Uređivanje timova', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => 'teams.delete', 'label' => 'Brisanje timova', 'sort_order' => 40],
                ['name' => 'Upravljanje članovima', 'slug' => 'manage_members', 'code' => TeamPermissions::MANAGE_MEMBERS, 'label' => 'Upravljanje članovima', 'sort_order' => 50],
                ['name' => 'Upravljanje ulogama', 'slug' => 'manage_roles', 'code' => TeamPermissions::MANAGE_ROLES, 'label' => 'Upravljanje ulogama', 'sort_order' => 60],
            ],
        ],
        [
            'name' => 'QR cjenici',
            'slug' => 'qr',
            'label' => 'QR cjenici',
            'description' => 'Upravljanje QR cjenicima, sekcijama, stavkama i prijevodima.',
            'icon' => 'qr-code',
            'sort_order' => 20,
            'items' => [
                ['name' => 'Upravljanje sadržajem', 'slug' => 'manage', 'code' => TeamPermissions::QR_MANAGE, 'label' => 'Upravljanje cjenikom', 'sort_order' => 10],
                ['name' => 'Arhiviranje i brisanje', 'slug' => 'delete', 'code' => TeamPermissions::QR_DELETE, 'label' => 'Arhiviranje i brisanje cjenika', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Poslovanje',
            'slug' => 'business',
            'label' => 'Poslovanje',
            'description' => 'Uređivanje javnih poslovnih informacija.',
            'icon' => 'building-office',
            'sort_order' => 30,
            'items' => [
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => TeamPermissions::BUSINESS_UPDATE, 'label' => 'Uređivanje poslovnih informacija', 'sort_order' => 10],
            ],
        ],
    ],

    'system_roles' => [
        [
            'name' => 'Superadministrator',
            'slug' => 'superadmin',
            'label' => 'Superadministrator',
            'description' => 'Sistemska uloga s punim pristupom.',
            'redirect_to' => null,
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
            'description' => 'Sistemska administratorska uloga tima.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 20,
            'permissions' => [
                TeamPermissions::MANAGE_MEMBERS,
                TeamPermissions::MANAGE_ROLES,
                'teams.view',
                'teams.create',
                'teams.update',
                'teams.delete',
                TeamPermissions::QR_MANAGE,
                TeamPermissions::QR_DELETE,
                TeamPermissions::BUSINESS_UPDATE,
            ],
        ],
        [
            'name' => 'Uređivač',
            'slug' => 'member',
            'label' => 'Uređivač',
            'description' => 'Može uređivati QR cjenik, prijevode i poslovne informacije.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 30,
            'permissions' => [
                TeamPermissions::QR_MANAGE,
                TeamPermissions::BUSINESS_UPDATE,
            ],
        ],
    ],
];
