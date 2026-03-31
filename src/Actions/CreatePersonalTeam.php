<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Auth\Events\Registered;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;

class CreatePersonalTeam
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        $team = Team::query()->create([
            'name' => "{$user->name}'s Team",
        ]);

        $membership = TeamMembership::ensureForUser($user, $team, true);
        $membership->syncRoles([(string) config('velora.roles.default_member_slug', 'member')], $team);
        $membership->assignRole('admin', $team);

        if (function_exists('set_current_team')) {
            set_current_team($team);
        }
    }
}
