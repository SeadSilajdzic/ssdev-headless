<?php

namespace Ssdev\Headless\Http\Middleware;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;

/**
 * For the package's guest pages only, so the application's own guest routes keep Laravel's behaviour.
 */
final class RedirectSignedInToDashboard extends RedirectIfAuthenticated
{
    protected function redirectTo(Request $request): string
    {
        return route('headless.dashboard');
    }
}
