<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use IvanBaric\Velora\Exceptions\InvalidInvitation;
use IvanBaric\Velora\Services\TeamInvitationService;

final class TeamInvitationController
{
    public function __construct(
        private readonly TeamInvitationService $teamInvitationService,
    ) {}

    public function show(string $token): View
    {
        try {
            return view('velora::invitations.accept', $this->teamInvitationService->preview(
                token: $token,
                hasValidSignature: request()->hasValidSignature(),
                ipAddress: request()->ip(),
            )->toViewData());
        } catch (InvalidInvitation $exception) {
            abort($exception->status, $exception->getMessage());
        }
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        try {
            $accepted = $this->teamInvitationService->acceptFromRequest($request, $token, $request->hasValidSignature());
        } catch (InvalidInvitation $exception) {
            abort($exception->status, $exception->getMessage());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        set_current_team($accepted->invitation->team_id);

        $routeName = (string) config('velora.invitations.accept_redirect_route', 'teams.settings');
        $target = $routeName !== '' && app('router')->has($routeName)
            ? route($routeName)
            : '/';

        return redirect($target)->with('status', $accepted->message);
    }
}
