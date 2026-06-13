<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

final class TeamPermissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const PRODUCTS_MANAGE = 'products.manage';

    public const BILLING_MANAGE = 'billing.manage';

    public const APPEARANCE_UPDATE = 'appearance.update';

    public const ANALYTICS_VIEW = 'analytics.view';

    public const SHARE_MANAGE = 'share.manage';

    public const LANGUAGES_MANAGE = 'languages.manage';

    public const PUBLIC_PAGE_UPDATE = 'public_page.update';

    public const SETTINGS_VIEW = 'settings.view';

    public const AUDIT_VIEW = 'audit.view';

    public const TEAMS_VIEW = 'teams.view';

    public const TEAMS_CREATE = 'teams.create';

    public const TEAMS_UPDATE = 'teams.update';

    public const TEAMS_DELETE = 'teams.delete';

    public const MANAGE_MEMBERS = 'teams.manage_members';

    public const MANAGE_ROLES = 'teams.manage_roles';

    public const QR_MANAGE = 'qr.manage';

    public const QR_DELETE = 'qr.delete';

    public const QR_SCHEDULES_MANAGE = 'qr.schedules.manage';

    public const BUSINESS_UPDATE = 'business.update';
}
