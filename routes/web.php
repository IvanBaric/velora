<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use IvanBaric\Velora\Http\Controllers\RolePreviewController;
use IvanBaric\Velora\Http\Controllers\TeamInvitationController;
use IvanBaric\Velora\Http\Controllers\TeamSwitchController;
use IvanBaric\Velora\Http\Livewire\TeamCreate;
use IvanBaric\Velora\Http\Livewire\TeamSettings;
use Livewire\Livewire;

$teamBasePath = '/'.trim((string) config('velora.routes.prefix', 'app'), '/')
    .'/'.trim((string) config('velora.routes.team_segment', 'team'), '/');

if (class_exists(Livewire::class)) {
    Route::middleware(config('velora.routes.authenticated_middleware'))->group(function () use ($teamBasePath): void {
        Route::get($teamBasePath, TeamSettings::class)->middleware('permission:teams.view')->name('teams.settings');
        Route::get($teamBasePath.'/create', TeamCreate::class)->middleware('permission:teams.create')->name('teams.create');
        Route::get($teamBasePath.'/switch/{team}', TeamSwitchController::class)->name('teams.switch');
        Route::post($teamBasePath.'/roles/preview/{role}', [RolePreviewController::class, 'start'])->name('teams.roles.preview');
        Route::delete($teamBasePath.'/roles/preview', [RolePreviewController::class, 'stop'])->name('teams.roles.preview.stop');
    });
}

Route::middleware(config('velora.routes.public_middleware'))
    ->controller(TeamInvitationController::class)
    ->group(function () use ($teamBasePath): void {
        Route::get($teamBasePath.'/invitation/{token}', 'show')->name('teams.invitation.accept');
        Route::post($teamBasePath.'/invitation/{token}', 'store')->name('teams.invitation.accept.store');
    });
