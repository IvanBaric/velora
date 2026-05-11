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
        'default_team_name' => env('VELORA_DEFAULT_TEAM_NAME', 'Zadani tim'),
        'system_team_name' => env('VELORA_SYSTEM_TEAM_NAME', 'Sistemski tim'),
    ],

    'session_key' => env('VELORA_CURRENT_TEAM_SESSION_KEY', 'velora.current_team_id'),

    'create_personal_team_on_registration' => env('VELORA_CREATE_PERSONAL_TEAM_ON_REGISTRATION', true),

    'create_personal_team_when_missing' => env('VELORA_CREATE_PERSONAL_TEAM_WHEN_MISSING', true),

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
        'prefix' => env('VELORA_ROUTES_PREFIX', 'app'),
        'team_segment' => env('VELORA_ROUTES_TEAM_SEGMENT', 'team'),
        'authenticated_middleware' => ['web', 'auth', 'set.team'],
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
            'name' => 'Korisnici',
            'slug' => 'users',
            'label' => 'Korisnici',
            'description' => 'Upravljanje korisnicima aplikacije.',
            'icon' => 'user-group',
            'sort_order' => 30,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'users.view', 'label' => 'Pregled korisnika', 'sort_order' => 10],
                ['name' => 'Kreiranje', 'slug' => 'create', 'code' => 'users.create', 'label' => 'Kreiranje korisnika', 'sort_order' => 20],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'users.update', 'label' => 'Uređivanje korisnika', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => 'users.delete', 'label' => 'Brisanje korisnika', 'sort_order' => 40],
            ],
        ],
        [
            'name' => 'Stranice',
            'slug' => 'pages',
            'label' => 'Stranice',
            'description' => 'Upravljanje sadržajem stranica.',
            'icon' => 'document-text',
            'sort_order' => 40,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'pages.view', 'label' => 'Pregled stranica', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'pages.update', 'label' => 'Uređivanje stranica', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Postavke prodavatelja',
            'slug' => 'dealer',
            'label' => 'Postavke prodavatelja',
            'description' => 'Upravljanje postavkama prodavatelja.',
            'icon' => 'cog-6-tooth',
            'sort_order' => 50,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'dealer.view', 'label' => 'Pregled postavki prodavatelja', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'dealer.update', 'label' => 'Uređivanje postavki prodavatelja', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Upiti',
            'slug' => 'inquiries',
            'label' => 'Upiti',
            'description' => 'Upravljanje upitima kupaca.',
            'icon' => 'inbox',
            'sort_order' => 60,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'inquiries.view', 'label' => 'Pregled upita', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'inquiries.update', 'label' => 'Uređivanje upita', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Otkup',
            'slug' => 'purchase_requests',
            'label' => 'Otkup',
            'description' => 'Upravljanje zahtjevima za otkup.',
            'icon' => 'hand-raised',
            'sort_order' => 70,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'purchase_requests.view', 'label' => 'Pregled otkupa', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'purchase_requests.update', 'label' => 'Uređivanje otkupa', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Vozila',
            'slug' => 'cars',
            'label' => 'Vozila',
            'description' => 'Upravljanje katalogom vozila.',
            'icon' => 'truck',
            'sort_order' => 80,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'cars.view', 'label' => 'Pregled vozila', 'sort_order' => 10],
                ['name' => 'Kreiranje', 'slug' => 'create', 'code' => 'cars.create', 'label' => 'Kreiranje vozila', 'sort_order' => 20],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'cars.update', 'label' => 'Uređivanje vozila', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => 'cars.delete', 'label' => 'Brisanje vozila', 'sort_order' => 40],
            ],
        ],
        [
            'name' => 'Specifikacije',
            'slug' => 'taxonomies',
            'label' => 'Specifikacije',
            'description' => 'Upravljanje specifikacijama kataloga.',
            'icon' => 'tag',
            'sort_order' => 90,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => 'taxonomies.view', 'label' => 'Pregled specifikacija', 'sort_order' => 10],
                ['name' => 'Kreiranje', 'slug' => 'create', 'code' => 'taxonomies.create', 'label' => 'Kreiranje specifikacija', 'sort_order' => 20],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => 'taxonomies.update', 'label' => 'Uređivanje specifikacija', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => 'taxonomies.delete', 'label' => 'Brisanje specifikacija', 'sort_order' => 40],
            ],
        ],
    ],

    'system_roles' => [
        [
            'name' => 'Superadministrator',
            'slug' => 'superadmin',
            'label' => 'Superadministrator',
            'description' => 'Sistemska uloga s punim pristupom.',
            'redirect_to' => '/app/dashboard',
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
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'pages.view',
                'pages.update',
                'dealer.view',
                'dealer.update',
                'inquiries.view',
                'inquiries.update',
                'purchase_requests.view',
                'purchase_requests.update',
                'cars.view',
                'cars.create',
                'cars.update',
                'cars.delete',
                'taxonomies.view',
                'taxonomies.create',
                'taxonomies.update',
                'taxonomies.delete',
            ],
        ],
        [
            'name' => 'Urednik',
            'slug' => 'editor',
            'label' => 'Urednik',
            'description' => 'Sistemska urednička uloga.',
            'redirect_to' => '/app/dashboard',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 30,
            'permissions' => [
                'cars.view',
                'cars.create',
                'cars.update',
                'cars.delete',
                'taxonomies.view',
                'taxonomies.create',
                'taxonomies.update',
                'taxonomies.delete',
            ],
        ],
        [
            'name' => 'Član',
            'slug' => 'member',
            'label' => 'Član',
            'description' => 'Zadana članska uloga.',
            'redirect_to' => '/app/dashboard',
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 40,
            'permissions' => [],
        ],
    ],
];
