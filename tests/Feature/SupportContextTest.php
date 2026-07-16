<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Velora\Http\Middleware\EnsureSuperadmin;
use IvanBaric\Velora\Models\Team;
use IvanBaric\Velora\Support\SupportContext;
use IvanBaric\Velora\Tests\Fixtures\User;
use IvanBaric\Velora\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SupportContextTest extends TestCase
{
    public function test_it_resolves_a_configured_superadmin_support_team(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_superadmin')->default(false);
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->timestamps();
        });

        $team = Team::query()->create(['name' => 'Tenant']);
        $superadmin = User::query()->create([
            'is_superadmin' => true,
            'current_team_id' => $team->getKey(),
        ]);
        $regularUser = User::query()->create([
            'is_superadmin' => false,
            'current_team_id' => $team->getKey(),
        ]);

        config()->set('velora.access.superadmin_attribute', 'is_superadmin');
        config()->set('velora.support_mode.enabled', true);

        $context = app(SupportContext::class);

        $this->assertTrue($context->activeFor($superadmin));
        $this->assertTrue($context->teamFor($superadmin)?->is($team));
        $this->assertFalse($context->activeFor($regularUser));
        $this->assertNull($context->teamFor($regularUser));

        config()->set('velora.support_mode.enabled', false);

        $this->assertFalse($context->activeFor($superadmin));

        config()->set('velora.support_mode.enabled', true);
        $superadmin->current_team_id = 0;

        $this->assertFalse($context->activeFor($superadmin));
    }

    public function test_superadmin_middleware_uses_the_configured_attribute(): void
    {
        config()->set('velora.access.superadmin_attribute', 'is_superadmin');
        $middleware = app(EnsureSuperadmin::class);
        $request = Request::create('/admin');
        $request->setUserResolver(static fn (): object => (object) ['is_superadmin' => true]);

        $response = $middleware->handle($request, static fn (): Response => response('ok'));

        $this->assertSame('ok', $response->getContent());

        $request->setUserResolver(static fn (): object => (object) ['is_superadmin' => false]);

        $this->expectException(HttpException::class);
        $middleware->handle($request, static fn (): Response => response('denied'));
    }
}
