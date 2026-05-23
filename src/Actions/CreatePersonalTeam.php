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
            $this->assignCurrentTeam($user, (int) $team->getKey());

            return $team;
        });
    }

    protected function assignCurrentTeam(Model $user, int $teamId): void
    {
        try {
            $schema = $user->getConnection()->getSchemaBuilder();
            $values = [];

            if ($schema->hasColumn($user->getTable(), 'current_team_id')) {
                $values['current_team_id'] = $teamId;
            }

            if ($schema->hasColumn($user->getTable(), 'team_id') && ! $user->getAttribute('team_id')) {
                $values['team_id'] = $teamId;
            }

            if ($values !== []) {
                $user->forceFill($values)->saveQuietly();
            }
        } catch (\Throwable) {
            // Host applications may use custom or read-only user models.
        }
    }
}
