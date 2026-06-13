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

    'plan_access' => [
        'resolver' => IvanBaric\Velora\Support\AllowAllPlanAccess::class,
        'default_plan' => 'starter',
    ],

    'current_team' => [
        'strategy' => env('VELORA_CURRENT_TEAM_STRATEGY', 'strict'),
        'default_team_name' => env('VELORA_DEFAULT_TEAM_NAME', 'Zadani tim'),
        'system_team_name' => env('VELORA_SYSTEM_TEAM_NAME', 'Sistemski tim'),
        'default_template' => env('VELORA_DEFAULT_TEAM_TEMPLATE', 'clean'),
        'default_business_type' => env('VELORA_DEFAULT_TEAM_BUSINESS_TYPE', 'other'),
    ],

    'create_personal_team_on_registration' => env('VELORA_CREATE_PERSONAL_TEAM_ON_REGISTRATION', false),

    'create_personal_team_when_missing' => env('VELORA_CREATE_PERSONAL_TEAM_WHEN_MISSING', true),

    'sync_defaults_on_boot' => env('VELORA_SYNC_DEFAULTS_ON_BOOT', env('APP_ENV') !== 'production'),

    'sync' => [
        'overwrite_existing' => false,
        'overwrite_superadmin' => false,
    ],

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
            'name' => 'Dashboard',
            'slug' => 'dashboard',
            'label' => 'Dashboard',
            'description' => 'Pregled osnovnog stanja, preporuka i brzih akcija.',
            'icon' => 'layout-grid',
            'sort_order' => 5,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => TeamPermissions::DASHBOARD_VIEW, 'label' => 'Pregled dashboarda', 'sort_order' => 10],
            ],
        ],
        [
            'name' => 'Sadržaj',
            'slug' => 'content',
            'label' => 'Sadržaj',
            'description' => 'Upravljanje ponudom i sadržajem javnih stranica.',
            'icon' => 'document-text',
            'sort_order' => 8,
            'items' => [
                ['name' => 'Javna stranica', 'slug' => 'public_page_update', 'code' => TeamPermissions::PUBLIC_PAGE_UPDATE, 'label' => 'Uređivanje javne stranice', 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Timovi',
            'slug' => 'teams',
            'label' => 'Timovi',
            'description' => 'Upravljanje timovima, članovima i ulogama.',
            'icon' => 'users',
            'sort_order' => 10,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => TeamPermissions::TEAMS_VIEW, 'label' => 'Pregled timova', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => TeamPermissions::TEAMS_UPDATE, 'label' => 'Uređivanje timova', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => TeamPermissions::TEAMS_DELETE, 'label' => 'Brisanje timova', 'sort_order' => 40],
                ['name' => 'Upravljanje članovima', 'slug' => 'manage_members', 'code' => TeamPermissions::MANAGE_MEMBERS, 'label' => 'Upravljanje članovima', 'sort_order' => 50],
                ['name' => 'Upravljanje ulogama', 'slug' => 'manage_roles', 'code' => TeamPermissions::MANAGE_ROLES, 'label' => 'Upravljanje ulogama', 'sort_order' => 60],
            ],
        ],
        [
            'name' => 'QR cjenici',
            'slug' => 'qr',
            'label' => 'QR cjenici',
            'description' => 'Upravljanje QR cjenicima, sekcijama, stavkama, prijevodima i povezanim alatima.',
            'icon' => 'qr-code',
            'sort_order' => 20,
            'items' => [
                ['name' => 'Upravljanje sadržajem', 'slug' => 'manage', 'code' => TeamPermissions::QR_MANAGE, 'label' => 'Upravljanje cjenikom', 'sort_order' => 10],
                ['name' => 'Arhiviranje i brisanje', 'slug' => 'delete', 'code' => TeamPermissions::QR_DELETE, 'label' => 'Arhiviranje i brisanje cjenika', 'sort_order' => 20],
                ['name' => 'Rasporedi', 'slug' => 'schedules_manage', 'code' => TeamPermissions::QR_SCHEDULES_MANAGE, 'label' => 'Upravljanje rasporedima i promocijama', 'sort_order' => 30],
                ['name' => 'Prijevodi', 'slug' => 'languages_manage', 'code' => TeamPermissions::LANGUAGES_MANAGE, 'label' => 'Upravljanje jezicima i prijevodima', 'sort_order' => 40],
                ['name' => 'Izgled', 'slug' => 'appearance_update', 'code' => TeamPermissions::APPEARANCE_UPDATE, 'label' => 'Uređivanje izgleda cjenika', 'sort_order' => 50],
                ['name' => 'Dijeljenje', 'slug' => 'share_manage', 'code' => TeamPermissions::SHARE_MANAGE, 'label' => 'Dijeljenje i QR materijali', 'sort_order' => 60],
                ['name' => 'Analitika', 'slug' => 'analytics_view', 'code' => TeamPermissions::ANALYTICS_VIEW, 'label' => 'Pregled analitike', 'sort_order' => 70],
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
        [
            'name' => 'Administracija',
            'slug' => 'administration',
            'label' => 'Administracija',
            'description' => 'Naplata, postavke, audit log i tehničke postavke tima.',
            'icon' => 'cog-6-tooth',
            'sort_order' => 40,
            'items' => [
                ['name' => 'Naplata', 'slug' => 'billing_manage', 'code' => TeamPermissions::BILLING_MANAGE, 'label' => 'Upravljanje naplatom', 'sort_order' => 10],
                ['name' => 'Postavke', 'slug' => 'settings_view', 'code' => TeamPermissions::SETTINGS_VIEW, 'label' => 'Pregled postavki', 'sort_order' => 20],
                ['name' => 'Audit log', 'slug' => 'audit_view', 'code' => TeamPermissions::AUDIT_VIEW, 'label' => 'Pregled audit loga', 'sort_order' => 30],
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
            'description' => 'Sistemska administratorska uloga tima s pristupom svim administrativnim modulima.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 20,
            'all_permissions' => true,
        ],
        [
            'name' => 'Uređivač',
            'slug' => 'member',
            'label' => 'Uređivač',
            'description' => 'Može uređivati operativni sadržaj: dashboard, QR cjenik, prijevode i poslovne informacije.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 30,
            'permissions' => [
                TeamPermissions::DASHBOARD_VIEW,
                TeamPermissions::QR_MANAGE,
                TeamPermissions::LANGUAGES_MANAGE,
                TeamPermissions::BUSINESS_UPDATE,
            ],
        ],
    ],
];
