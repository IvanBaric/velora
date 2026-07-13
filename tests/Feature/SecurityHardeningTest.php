<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use IvanBaric\Velora\Actions\AcceptInvitationAction;
use IvanBaric\Velora\Actions\PreviewInvitationAction;
use IvanBaric\Velora\Actions\ResendInvitationAction;
use IvanBaric\Velora\Actions\RevokeInvitationAction;
use IvanBaric\Velora\Actions\SendInvitationAction;
use IvanBaric\Velora\Enums\TeamInvitationStatus;
use IvanBaric\Velora\Enums\TeamMembershipStatus;
use IvanBaric\Velora\Exceptions\InvalidInvitation;
use IvanBaric\Velora\Http\Middleware\SetTeam;
use IvanBaric\Velora\Models\Permission;
use IvanBaric\Velora\Models\PermissionItem;
use IvanBaric\Velora\Models\Role;
use IvanBaric\Velora\Models\TeamInvitation;
use IvanBaric\Velora\Models\TeamMembership;
use IvanBaric\Velora\Models\UserRole;
use IvanBaric\Velora\Support\RolePreview;
use IvanBaric\Velora\Support\TeamPermissions;
use IvanBaric\Velora\Tests\Fixtures\User;
use IvanBaric\Velora\Tests\TestCase;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

final class SecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSecuritySchema();
    }

    public function test_role_preview_can_restrict_but_never_expand_real_permissions(): void
    {
        [$user, $teamId] = $this->createActiveUserAndTeam();
        [$actualPermission, $previewPermission] = $this->createPermissionItems();
        $actualRole = $this->createRole($teamId, 'urednik', [$actualPermission->getKey()]);
        $previewRole = $this->createRole($teamId, 'administrator-preview', [$previewPermission->getKey()]);

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'team_id' => $teamId,
            'role_id' => $actualRole->getKey(),
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->hasPermission($actualPermission->code, $teamId));
        $this->assertFalse($user->hasPermission($previewPermission->code, $teamId));

        app(RolePreview::class)->start($previewRole, $teamId);

        $this->assertFalse($user->hasPermission($actualPermission->code, $teamId));
        $this->assertFalse($user->hasPermission($previewPermission->code, $teamId));
    }

    public function test_inactive_membership_cannot_reuse_stored_roles_or_permissions(): void
    {
        [$user, $teamId, $membership] = $this->createActiveUserAndTeam();
        [$permission] = $this->createPermissionItems();
        $role = $this->createRole($teamId, 'urednik', [$permission->getKey()]);

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'team_id' => $teamId,
            'role_id' => $role->getKey(),
            'assigned_at' => now(),
        ]);

        $this->assertTrue($user->hasRole($role, $teamId));
        $this->assertTrue($user->hasPermission($permission->code, $teamId));

        $membership->forceFill(['status' => TeamMembershipStatus::Revoked])->save();

        $this->assertFalse($user->hasRole($role, $teamId));
        $this->assertFalse($user->hasPermission($permission->code, $teamId));
    }

    public function test_invited_user_creation_rolls_back_when_locked_invitation_does_not_match(): void
    {
        $teamId = $this->createTeam();
        $role = $this->createRole($teamId, 'suradnik', []);
        $invitation = TeamInvitation::query()->withoutGlobalScopes()->create([
            'team_id' => $teamId,
            'email' => 'pozvani@example.test',
            'role_slug' => $role->slug,
            'status' => TeamInvitationStatus::Pending,
            'token_hash' => hash('sha256', 'test-token'),
            'expires_at' => now()->addHour(),
        ]);

        try {
            app(AcceptInvitationAction::class)->executeWithUserResolver(
                $invitation,
                fn (): User => User::query()->create([
                    'name' => 'Pogrešan korisnik',
                    'email' => 'drugi@example.test',
                    'password' => 'hashed-test-password',
                    'current_team_id' => $teamId,
                ]),
            );

            $this->fail('Prihvat pozivnice s drugim korisničkim računom mora biti odbijen.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('users', ['email' => 'drugi@example.test']);
            $this->assertSame(
                TeamInvitationStatus::Pending,
                $invitation->fresh()->status,
            );
        }
    }

    public function test_set_team_middleware_is_persistent_for_livewire_updates(): void
    {
        $this->assertContains(
            SetTeam::class,
            app(PersistentMiddleware::class)->getPersistentMiddleware(),
        );
    }

    public function test_stale_actions_cannot_revoke_or_reopen_an_accepted_invitation(): void
    {
        Mail::fake();

        $teamId = $this->createTeam();
        $role = $this->createRole($teamId, 'suradnik', []);
        $invitation = TeamInvitation::query()->withoutGlobalScopes()->create([
            'team_id' => $teamId,
            'email' => 'prihvaceni@example.test',
            'role_slug' => $role->slug,
            'status' => TeamInvitationStatus::Pending,
            'token_hash' => hash('sha256', 'stari-token'),
            'expires_at' => now()->addHour(),
        ]);
        $staleInvitation = $invitation->fresh();

        DB::table('team_invitations')
            ->where('id', $invitation->getKey())
            ->update([
                'status' => TeamInvitationStatus::Accepted->value,
                'accepted_at' => now(),
            ]);

        $revokeResult = app(RevokeInvitationAction::class)->execute($staleInvitation);

        $this->assertFalse($revokeResult->success);
        $this->assertSame(TeamInvitationStatus::Accepted, $invitation->fresh()->status);

        try {
            app(ResendInvitationAction::class)->execute($staleInvitation);
            $this->fail('Prihvaćena pozivnica ne smije se ponovno aktivirati zastarjelim zahtjevom.');
        } catch (ValidationException) {
            $this->assertSame(TeamInvitationStatus::Accepted, $invitation->fresh()->status);
            $this->assertSame(hash('sha256', 'stari-token'), $invitation->fresh()->token_hash);
            Mail::assertNothingSent();
        }
    }

    public function test_non_owner_cannot_issue_an_owner_invitation(): void
    {
        Mail::fake();

        [$user, $teamId] = $this->createActiveUserAndTeam();
        $manageMembers = $this->createPermissionItem(TeamPermissions::MANAGE_MEMBERS);
        $managerRole = $this->createRole($teamId, 'voditelj', [$manageMembers->getKey()]);
        $invitedRole = $this->createRole($teamId, 'suradnik', []);

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'team_id' => $teamId,
            'role_id' => $managerRole->getKey(),
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        try {
            app(SendInvitationAction::class)->execute(
                email: 'novi-vlasnik@example.test',
                roleSlug: $invitedRole->slug,
                teamId: $teamId,
                actorUserId: (int) $user->getKey(),
                isOwner: true,
            );
            $this->fail('Nevlasnik ne smije izdati pozivnicu s vlasničkim pristupom.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('team_invitations', [
                'email' => 'novi-vlasnik@example.test',
            ]);
            Mail::assertNothingSent();
        }
    }

    public function test_invalid_signature_cannot_expire_a_pending_invitation(): void
    {
        $teamId = $this->createTeam();
        $role = $this->createRole($teamId, 'suradnik', []);
        $plainToken = 'potpisani-test-token';
        $invitation = TeamInvitation::query()->withoutGlobalScopes()->create([
            'team_id' => $teamId,
            'email' => 'potpis@example.test',
            'role_slug' => $role->slug,
            'status' => TeamInvitationStatus::Pending,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
        ]);

        try {
            app(PreviewInvitationAction::class)->execute($plainToken, false, '127.0.0.1');
            $this->fail('Pozivnica bez valjanog URL potpisa mora biti odbijena.');
        } catch (InvalidInvitation) {
            $this->assertSame(TeamInvitationStatus::Pending, $invitation->fresh()->status);
        }
    }

    /**
     * @return array{User, int, TeamMembership}
     */
    private function createActiveUserAndTeam(): array
    {
        $teamId = $this->createTeam();
        $user = User::query()->create([
            'name' => 'Sigurnosni test',
            'email' => 'security@example.test',
            'password' => 'hashed-test-password',
            'current_team_id' => $teamId,
        ]);
        $membership = TeamMembership::query()->create([
            'team_id' => $teamId,
            'user_id' => $user->getKey(),
            'status' => TeamMembershipStatus::Active,
            'is_owner' => false,
            'joined_at' => now(),
        ]);

        return [$user, $teamId, $membership];
    }

    private function createTeam(): int
    {
        return (int) DB::table('teams')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sigurnosni tim',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{PermissionItem, PermissionItem}
     */
    private function createPermissionItems(): array
    {
        $permission = Permission::query()->create([
            'name' => 'Sigurnost',
            'slug' => 'security-'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);

        $actualPermission = PermissionItem::query()->create([
            'permission_id' => $permission->getKey(),
            'name' => 'Stvarna dozvola',
            'slug' => 'actual-'.Str::lower(Str::random(8)),
            'code' => 'security.actual.'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);
        $previewPermission = PermissionItem::query()->create([
            'permission_id' => $permission->getKey(),
            'name' => 'Jača dozvola',
            'slug' => 'preview-'.Str::lower(Str::random(8)),
            'code' => 'security.preview.'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);

        return [$actualPermission, $previewPermission];
    }

    private function createPermissionItem(string $code): PermissionItem
    {
        $permission = Permission::query()->create([
            'name' => 'Upravljanje timom',
            'slug' => 'team-management-'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);

        return PermissionItem::query()->create([
            'permission_id' => $permission->getKey(),
            'name' => 'Upravljanje članovima',
            'slug' => 'manage-members-'.Str::lower(Str::random(8)),
            'code' => $code,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, int>  $permissionItemIds
     */
    private function createRole(int $teamId, string $slug, array $permissionItemIds): Role
    {
        $role = Role::query()->create([
            'team_id' => $teamId,
            'name' => Str::headline($slug),
            'slug' => $slug,
            'is_system' => false,
            'is_locked' => false,
            'assignable' => true,
            'is_active' => true,
        ]);
        $role->permissionItems()->sync($permissionItemIds);

        return $role;
    }

    private function createSecuritySchema(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->timestamps();
        });

        Schema::create('team_memberships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->boolean('is_owner')->default(false);
            $table->unsignedBigInteger('invited_by_user_id')->nullable();
            $table->string('invited_email')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'team_id']);
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id');
            $table->string('email');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('role_slug')->nullable();
            $table->boolean('is_owner')->default(false);
            $table->string('status', 20);
            $table->unsignedBigInteger('invited_by_user_id')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('resent_count')->default(0);
            $table->timestamps();
        });

        Schema::create('team_invitation_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('team_invitation_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('type', 50);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
