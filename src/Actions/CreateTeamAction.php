<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;

final class CreateTeamAction
{
    public function execute(Model $user, string $name): Team
    {
        return DB::transaction(function () use ($user, $name): Team {
            /** @var Team $team */
            $team = Team::query()->create([
                'name' => $name,
            ]);

            $membership = TeamMembership::ensureForUser($user, $team, true);
            $membership->assignRole('admin', $team);

            return $team;
        });
    }
}
