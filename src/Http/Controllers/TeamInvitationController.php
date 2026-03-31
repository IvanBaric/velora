<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use IvanBaric\Velora\Services\TeamInvitationService;

final class TeamInvitationController
{
    public function __construct(
        private readonly TeamInvitationService $teamInvitationService,
    ) {
    }

    public function show(string $token): View
    {
        return view('velora::invitations.accept', $this->teamInvitationService->previewData($token));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        return $this->teamInvitationService->accept($request, $token);
    }
}
