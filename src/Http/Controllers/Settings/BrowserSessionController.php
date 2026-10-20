<?php

namespace Ssdev\Headless\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\BrowserSessionService;
use Ssdev\Headless\Http\Requests\Settings\LogoutOtherSessionsRequest;
use Ssdev\Headless\Support\Flash;

final class BrowserSessionController
{
    public function destroy(Request $request, string $session, BrowserSessionService $sessions): RedirectResponse
    {
        abort_unless($sessions->logout($request->user(), $session), 404);

        return back()->with(Flash::SUCCESS, __('headless::settings.sessions.logged_out'));
    }

    public function destroyOthers(LogoutOtherSessionsRequest $request, BrowserSessionService $sessions): RedirectResponse
    {
        $sessions->logoutOthers($request->user(), $request->session()->getId());

        return back()->with(Flash::SUCCESS, __('headless::settings.sessions.others_logged_out'));
    }
}
