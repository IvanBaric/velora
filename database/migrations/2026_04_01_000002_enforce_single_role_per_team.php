<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_roles')) {
            return;
        }

        $this->deduplicateUserRoles();

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropUnique('user_roles_user_id_team_id_role_id_unique');
            $table->unique(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_roles')) {
            return;
        }

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropUnique('user_roles_user_id_team_id_unique');
            $table->unique(['user_id', 'team_id', 'role_id']);
        });
    }

    protected function deduplicateUserRoles(): void
    {
        $seenAssignments = [];
        $deleteIds = [];

        DB::table('user_roles')
            ->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select([
                'user_roles.id',
                'user_roles.user_id',
                'user_roles.team_id',
                'user_roles.assigned_at',
            ])
            ->orderBy('user_roles.user_id')
            ->orderBy('user_roles.team_id')
            ->orderByRaw('coalesce(roles.sort_order, 2147483647)')
            ->orderByRaw('case when user_roles.assigned_at is null then 1 else 0 end')
            ->orderByDesc('user_roles.assigned_at')
            ->orderByDesc('user_roles.id')
            ->cursor()
            ->each(function (object $assignment) use (&$seenAssignments, &$deleteIds): void {
                $key = sprintf('%s:%s', (string) $assignment->user_id, (string) $assignment->team_id);

                if (isset($seenAssignments[$key])) {
                    $deleteIds[] = (int) $assignment->id;

                    return;
                }

                $seenAssignments[$key] = true;
            });

        foreach (array_chunk($deleteIds, 500) as $chunk) {
            DB::table('user_roles')
                ->whereIn('id', $chunk)
                ->delete();
        }
    }
};
