<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function execute(Model $user): Model
    {
        return DB::transaction(function () use ($user): Model {
            $teamTable = velora_team_table();
            $payload = [
                'name' => __('Tim suradnika :name', ['name' => $user->name]),
            ];

            if (Schema::hasColumn($teamTable, 'owner_id')) {
                $payload['owner_id'] = $user->getKey();
            }

            if (Schema::hasColumn($teamTable, 'uuid')) {
                $payload['uuid'] = (string) Str::uuid();
            }

            if (Schema::hasColumn($teamTable, 'template')) {
                $payload['template'] = (string) config('velora.current_team.default_template', 'clean');
            }

            if (Schema::hasColumn($teamTable, 'plan_code')) {
                $payload['plan_code'] = (string) config('velora.plan_access.default_plan', 'starter');
            }

            if (Schema::hasColumn($teamTable, 'shortcode')) {
                $teamClass = velora_team_model();
                $payload['shortcode'] = method_exists($teamClass, 'generateUniqueShortcode')
                    ? $teamClass::generateUniqueShortcode()
                    : Str::lower(Str::random(6));
            }

            if (Schema::hasColumn($teamTable, 'business_type')) {
                $payload['business_type'] = (string) config('velora.current_team.default_business_type', 'other');
            }

            if (Schema::hasColumn($teamTable, 'is_active')) {
                $payload['is_active'] = true;
            }

            $team = velora_team_query()->create($payload);

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
