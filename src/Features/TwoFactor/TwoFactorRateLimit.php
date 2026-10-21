<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class TwoFactorRateLimit
{
    public const NAME = 'headless.two-factor';

    private const CODE_FIELD = 'code';

    public function __construct(private Repository $config) {}

    public function register(): void
    {
        RateLimiter::for(self::NAME, fn (Request $request) => Limit::perMinute((int) $this->config->get('headless.rate_limits.two_factor'))
            ->by($this->key($request))
            ->response(fn (Request $request, array $headers) => $this->tooManyAttempts($request, $headers)));
    }

    /**
     * A sign-in challenge has no signed-in user yet, so it counts per pending login or API challenge.
     */
    private function key(Request $request): string
    {
        $pendingLogin = $request->hasSession() ? $request->session()->get('login.id') : null;
        $challenge = $request->input('challenge_token');

        return match (true) {
            $pendingLogin !== null => 'login|'.$pendingLogin,
            is_string($challenge) => 'challenge|'.hash('sha256', $challenge),
            $request->user() !== null => 'user|'.$request->user()->getAuthIdentifier(),
            default => 'ip|'.$request->ip(),
        };
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function tooManyAttempts(Request $request, array $headers): Response
    {
        $message = __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return back()->withErrors([self::CODE_FIELD => $message]);
    }
}
