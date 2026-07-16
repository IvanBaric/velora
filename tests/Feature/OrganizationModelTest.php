<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Velora\Models\Organization;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Tests\TestCase;

final class OrganizationModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('template')->nullable();
            $table->string('shortcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_public_organization_profile_is_reusable_without_host_integrations(): void
    {
        app()->setLocale('hr');

        $team = Team::query()->create(['name' => 'Reusable team']);
        $organization = Organization::query()->create([
            'team_id' => $team->getKey(),
            'name' => ['hr' => 'Višekratna organizacija', 'en' => 'Reusable organization'],
            'description' => ['hr' => 'Javni profil'],
            'is_active' => true,
        ]);

        $this->assertNotNull($organization->uuid);
        $this->assertSame('visekratna-organizacija', $organization->slug);
        $this->assertSame('Višekratna organizacija', $organization->localized('name', 'hr'));
        $this->assertTrue($organization->active()->whereKey($organization->getKey())->exists());
        $this->assertTrue($organization->team->is($team));
        $this->assertTrue(Organization::findByUuid($organization->uuid)?->is($organization));
    }
}
