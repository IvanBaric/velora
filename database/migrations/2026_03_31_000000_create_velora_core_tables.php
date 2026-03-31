<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('team_memberships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->boolean('is_owner')->default(false);
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invited_email')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->string('redirect_to')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('assignable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'slug']);
            $table->index(['team_id', 'is_active']);
            $table->index(['is_system', 'assignable']);
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'team_id', 'role_id']);
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'role_id']);
            $table->index('expires_at');
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('role_slug')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('resent_count')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'email']);
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'email']);
        });

        Schema::create('team_invitation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_invitation_id')->constrained('team_invitations')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['team_invitation_id', 'type']);
            $table->index(['team_id', 'type']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('permission_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code')->unique();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['permission_id', 'slug']);
            $table->index(['permission_id', 'is_active']);
        });

        Schema::create('role_permission_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_item_id']);
            $table->index(['permission_item_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission_items');
        Schema::dropIfExists('permission_items');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('team_invitation_events');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('team_memberships');
        Schema::dropIfExists('teams');
    }
};
