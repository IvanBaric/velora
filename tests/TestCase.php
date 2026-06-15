<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Tests\Fixtures\User;
use IvanBaric\Velora\VeloraServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            VeloraServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('velora.sync_defaults_on_boot', false);
        $app['config']->set('velora.models.user', User::class);
        $app['config']->set('velora.models.team', Team::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshAccessSchema();
    }

    protected function refreshAccessSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('role_permission_items');
            Schema::dropIfExists('permission_items');
            Schema::dropIfExists('permissions');
            Schema::dropIfExists('roles');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

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
        });

        Schema::create('permission_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
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
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->nullable();
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
        });

        Schema::create('role_permission_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_item_id')->constrained('permission_items')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_item_id']);
        });
    }
}
