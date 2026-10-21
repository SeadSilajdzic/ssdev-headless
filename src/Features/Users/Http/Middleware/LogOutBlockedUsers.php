<?php

namespace Ssdev\Headless\Features\Users\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Features\Users\UserStatus;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs in the web and api groups, so sessions and tokens made before the block stop working on their next request.
 */
final class LogOutBlockedUsers
{
    public function __construct(
        private AuthFactory $auth,
        private PersonalAccessTokenService $tokens,
    ) {}

    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = $request->user($guard);

        if (! $user instanceof User || UserStatus::of($user) !== UserStatus::Blocked) {
            return $next($request);
        }

        $message = __('headless::users.errors.blocked');

        if ($request->expectsJson()) {
            $this->tokens->currentToken($user)?->delete();

            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        $sessionGuard = $this->auth->guard($guard);

        if ($sessionGuard instanceof StatefulGuard) {
            $sessionGuard->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
