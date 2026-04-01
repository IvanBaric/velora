<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userModel = velora_user_model();
        $userTable = velora_user_table();

        Schema::create('team_membership_events', function (Blueprint $table) use ($userModel, $userTable): void {
            $table->id();
            $table->foreignId('team_membership_id')->constrained('team_memberships')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor($userModel, 'actor_user_id')->nullable()->constrained($userTable)->nullOnDelete();
            $table->string('type', 50);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['team_membership_id', 'type']);
            $table->index(['team_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_membership_events');
    }
};
