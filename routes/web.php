<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use IvanBaric\Velora\Http\Controllers\TeamInvitationController;
use IvanBaric\Velora\Http\Controllers\TeamSwitchController;
use IvanBaric\Velora\Http\Livewire\TeamCreate;
use IvanBaric\Velora\Http\Livewire\TeamSettings;

Route::middleware(config('velora.routes.authenticated_middleware'))->group(function (): void {
    Route::get('/app/team', TeamSettings::class)->name('teams.settings');
    Route::get('/app/team/create', TeamCreate::class)->name('teams.create');
    Route::get('/app/team/switch/{team}', TeamSwitchController::class)->name('teams.switch');
});

Route::middleware(config('velora.routes.public_middleware'))
    ->controller(TeamInvitationController::class)
    ->group(function (): void {
        Route::get('/app/team/invitation/{token}', 'show')->name('teams.invitation.accept');
        Route::post('/app/team/invitation/{token}', 'store')->name('teams.invitation.accept.store');
    });
