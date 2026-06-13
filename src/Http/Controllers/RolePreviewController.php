<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Support\RolePreview;
use IvanBaric\Velora\Support\TeamPermissions;

class RolePreviewController
{
    public function start(Request $request, Role $role, RolePreview $preview): RedirectResponse
    {
        $team = team();

        abort_unless($request->user()?->hasPermission(TeamPermissions::MANAGE_ROLES, $team), 403);
        abort_unless($role->is_active && ($role->team_id === null || (int) $role->team_id === (int) $team->getKey()), 404);

        $preview->start($role, (int) $team->getKey());

        return new RedirectResponse(route((string) config('velora.role_preview.redirect_route', 'teams.settings')));
    }

    public function stop(RolePreview $preview): RedirectResponse
    {
        $preview->stop();

        return new RedirectResponse(route((string) config('velora.role_preview.exit_redirect_route', 'teams.settings')));
    }
}
