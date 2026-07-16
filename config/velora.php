<?php

declare(strict_types=1);

use IvanBaric\Velora\Models\Organization;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\AllowAllPlanAccess;
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
            | Example: 'auth_layout' => 'layouts::auth.split'
            | Then Velora will render the configured auth layout component.
            */
            'auth_layout' => 'layouts::auth',
        ],
    ],

    'models' => [
        'user' => env('VELORA_USER_MODEL'),
        'team' => env('VELORA_TEAM_MODEL', Team::class),
        'organization' => env('VELORA_ORGANIZATION_MODEL', Organization::class),
    ],

    'access' => [
        'superadmin_attribute' => env('VELORA_SUPERADMIN_ATTRIBUTE'),
    ],

    'support_mode' => [
        'enabled' => env('VELORA_SUPPORT_MODE_ENABLED', false),
        'superadmin_attribute' => env('VELORA_SUPPORT_MODE_SUPERADMIN_ATTRIBUTE'),
        'team_id_attribute' => env('VELORA_SUPPORT_MODE_TEAM_ID_ATTRIBUTE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Overrides
    |--------------------------------------------------------------------------
    |
    | Override effective permissions without changing role records in the
    | database. Use permission codes such as "teams.update", or team aliases
    | such as "can_change_team_name" for common team capabilities.
    |
    | Boolean false denies a capability even if a role has it or the member is
    | an owner. Boolean true grants a capability even if the role does not have
    | a matching permission item. Missing keys fall back to normal RBAC.
    |
    */
    'authorization' => [
        'overrides' => [
            'owner' => [
                // TeamPermissions::TEAMS_UPDATE => false,
                // 'can_change_team_name' => false,
            ],
            'roles' => [
                // 'admin' => [
                //     TeamPermissions::MANAGE_ROLES => false,
                // ],
            ],
        ],
    ],

    'plan_access' => [
        'resolver' => AllowAllPlanAccess::class,
        'default_plan' => 'starter',
    ],

    'current_team' => [
        'strategy' => env('VELORA_CURRENT_TEAM_STRATEGY', 'strict'),
        'default_team_name' => env('VELORA_DEFAULT_TEAM_NAME', 'Zadani tim'),
        'system_team_name' => env('VELORA_SYSTEM_TEAM_NAME', 'Sistemski tim'),
        'default_attributes' => [
            'template' => env('VELORA_DEFAULT_TEAM_TEMPLATE', 'clean'),
            'is_active' => true,
        ],
        'user_team_id_column' => env('VELORA_USER_TEAM_ID_COLUMN', 'current_team_id'),
        'user_team_relation' => env('VELORA_USER_TEAM_RELATION'),
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
        'redirect_route' => env('VELORA_ROLE_PREVIEW_REDIRECT_ROUTE', 'teams.settings'),
        'exit_redirect_route' => env('VELORA_ROLE_PREVIEW_EXIT_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'team_switch' => [
        'redirect_route' => env('VELORA_TEAM_SWITCH_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'team_settings' => [
        'leave_redirect_route' => env('VELORA_TEAM_SETTINGS_LEAVE_REDIRECT_ROUTE', 'teams.settings'),
    ],

    'invitations' => [
        'expires_after_days' => (int) env('VELORA_INVITATION_EXPIRES_AFTER_DAYS', 7),
        'accept_redirect_route' => env('VELORA_INVITATION_ACCEPT_REDIRECT_ROUTE', 'dashboard'),
    ],

    'mail' => [
        'invitation_view' => env('VELORA_INVITATION_MAIL_VIEW', 'velora::mail.invitation'),
        'member_joined_view' => env('VELORA_MEMBER_JOINED_MAIL_VIEW', 'velora::mail.member-joined'),
        'invitation_subject' => env('VELORA_INVITATION_MAIL_SUBJECT', 'Poziv za suradnju: :team'),
        'member_joined_subject' => env('VELORA_MEMBER_JOINED_MAIL_SUBJECT', 'Novi suradnik organizacije: :team'),
    ],

    'routes' => [
        'prefix' => env('VELORA_ROUTES_PREFIX', 'app'),
        'team_segment' => env('VELORA_ROUTES_TEAM_SEGMENT', 'teams'),
        'authenticated_middleware' => ['web', 'auth', 'set.team'],
        'public_middleware' => ['web', 'throttle:30,1'],
    ],

    'permissions' => [
        [
            'name' => 'Organizacija',
            'slug' => 'teams',
            'label' => 'Organizacija',
            'description' => 'Upravljanje organizacijom, suradnicima i ulogama.',
            'icon' => 'users',
            'sort_order' => 10,
            'items' => [
                ['name' => 'Pregled', 'slug' => 'view', 'code' => TeamPermissions::TEAMS_VIEW, 'label' => 'Pregled organizacije', 'sort_order' => 10],
                ['name' => 'Uređivanje', 'slug' => 'update', 'code' => TeamPermissions::TEAMS_UPDATE, 'label' => 'Uređivanje organizacije', 'sort_order' => 30],
                ['name' => 'Brisanje', 'slug' => 'delete', 'code' => TeamPermissions::TEAMS_DELETE, 'label' => 'Brisanje organizacije', 'sort_order' => 40],
                ['name' => 'Upravljanje suradnicima', 'slug' => 'manage_members', 'code' => TeamPermissions::MANAGE_MEMBERS, 'label' => 'Upravljanje suradnicima', 'sort_order' => 50],
                ['name' => 'Upravljanje ulogama', 'slug' => 'manage_roles', 'code' => TeamPermissions::MANAGE_ROLES, 'label' => 'Upravljanje ulogama', 'sort_order' => 60],
            ],
        ],
    ],

    'permission_sources' => [
        'pages',
        'blog',
        'gallery',
        'settings',
        'taxonomy',
        'status',
        'seo',
        'billing',
        'plans',
        'language',
        'eav',
        'meta',
        'template-engine',
    ],

    'system_roles' => [
        [
            'name' => 'Owner',
            'slug' => 'owner',
            'label' => 'Owner',
            'description' => 'Sistemska uloga s punim pristupom organizaciji.',
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
            'description' => 'Administratorska uloga organizacije.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 20,
            'all_permissions' => true,
        ],
        [
            'name' => 'Suradnik',
            'slug' => 'member',
            'label' => 'Suradnik',
            'description' => 'Zadana uloga suradnika organizacije.',
            'redirect_to' => null,
            'is_system' => true,
            'is_locked' => true,
            'assignable' => true,
            'is_active' => true,
            'sort_order' => 30,
            'permissions' => [
                TeamPermissions::TEAMS_VIEW,
            ],
        ],
    ],
];
