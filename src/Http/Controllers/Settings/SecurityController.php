<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Account\BrowserSessionService;
use Ssdev\Headless\Account\SessionRepository;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Http\Resources\BrowserSessionResource;

final class SecurityController
{
    public function edit(Request $request, BrowserSessionService $browserSessions, SessionRepository $sessions): Response
    {
        return HeadlessPage::render('Settings/Security', [
            'sessionsSupported' => $sessions->supportsBrowserSessions(),
            'sessions' => BrowserSessionResource::collection($browserSessions->forUser($request->user(), $request->session()->getId()))->resolve($request),
            'logoutOthersUrl' => route('headless.sessions.destroy-others'),
        ]);
    }
}
