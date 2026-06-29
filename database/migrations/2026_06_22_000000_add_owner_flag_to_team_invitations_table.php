<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_invitations') || Schema::hasColumn('team_invitations', 'is_owner')) {
            return;
        }

        Schema::table('team_invitations', function (Blueprint $table): void {
            $table->boolean('is_owner')->default(false)->after('role_slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('team_invitations') || ! Schema::hasColumn('team_invitations', 'is_owner')) {
            return;
        }

        Schema::table('team_invitations', function (Blueprint $table): void {
            $table->dropColumn('is_owner');
        });
    }
};
