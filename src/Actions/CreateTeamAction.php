<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use IvanBaric\Corexis\Concerns\AuthorizesActions;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Support\TeamPermissions;

final class CreateTeamAction
{
    use AuthorizesActions;

    public function execute(Model $user, string $name): Model
    {
        $this->authorizeActionOrFail(TeamPermissions::TEAMS_CREATE);

        $name = $this->normalizeName($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('Naziv organizacije je obavezan.'),
            ]);
        }

        if (mb_strlen($name) > 255) {
            throw ValidationException::withMessages([
                'name' => __('Naziv organizacije ne smije biti duži od 255 znakova.'),
            ]);
        }

        return DB::transaction(function () use ($user, $name): Model {
            $teamTable = velora_team_table();
            $payload = [
                'name' => $name,
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
            $result = $membership->assignRole('admin', $team);

            if (! $result->ok) {
                throw ValidationException::withMessages([
                    'name' => $result->message,
                ]);
            }

            return $team;
        });
    }

    protected function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }
}
