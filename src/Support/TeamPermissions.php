<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Support;

final class TeamPermissions
{
    public const TEAMS_VIEW = 'teams.view';

    public const TEAMS_CREATE = 'teams.create';

    public const TEAMS_UPDATE = 'teams.update';

    public const TEAMS_DELETE = 'teams.delete';

    public const MANAGE_MEMBERS = 'teams.manage_members';

    public const MANAGE_ROLES = 'teams.manage_roles';
}
