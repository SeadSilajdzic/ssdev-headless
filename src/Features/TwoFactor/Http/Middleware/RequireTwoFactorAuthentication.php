<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Middleware;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\TwoFactor\TwoFactorRequirement;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Features\TwoFactor\TwoFactorStatus;
use Ssdev\Headless\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs in the web and api groups, so it also covers the application's own routes.
 */
final class RequireTwoFactorAuthentication
{
    // Everything a user needs to turn two-factor authentication on, or to leave.
    private const EXEMPT_ROUTES = [
        'headless.two-factor.*',
        'two-factor.login*',
        'password.confirm*',
        'password.confirmation',
        'logout',
        'verification.*',
        'headless.api.two-factor.*',
        'headless.api.auth.*',
        'headless.api.me',
    ];

    public function __construct(
        private TwoFactorRequirement $requirement,
        private TwoFactorService $twoFactor,
    ) {}

    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = $request->user($guard);

        if (! $user instanceof User
            || $request->routeIs(...self::EXEMPT_ROUTES)
            || ! $this->requirement->appliesTo($user)
            || $this->twoFactor->status($user) === TwoFactorStatus::Enabled) {
            return $next($request);
        }

        $message = __('headless::two_factor.required');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('headless.two-factor.edit')->with(Flash::ERROR, $message);
    }
}
