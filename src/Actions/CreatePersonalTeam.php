<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Models\TeamMembership;

class CreatePersonalTeam
{
    public function handle(Registered $event): void
    {
        $team = $this->execute($event->user);

        if (function_exists('set_current_team')) {
            set_current_team($team);
        }
    }

    public function execute(Model $user): Team
    {
        return DB::transaction(function () use ($user): Team {
            $team = Team::query()->create([
                'name' => 'Tim korisnika '.$user->name,
            ]);

            $membership = TeamMembership::ensureForUser($user, $team, true);
            $membership->assignRole('admin', $team);

            return $team;
        });
    }
}
