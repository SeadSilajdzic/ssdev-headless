<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RateLimits
{
    public const LOGIN = 'headless.login';

    public const REGISTRATION = 'headless.registration';

    public const PASSWORD_RESET = 'headless.password-reset';

    public const VERIFICATION = 'headless.verification';

    public const REAUTHENTICATION = 'headless.reauthentication';

    public const EMAIL_CHANGE = 'headless.email-change';

    private const EMAIL_FIELD = 'email';

    public function __construct(private Repository $config) {}

    public function register(): void
    {
        RateLimiter::for(self::LOGIN, fn (Request $request) => [
            $this->formLimit('login', $this->emailKey($request), lockout: true),
            $this->formLimit('login_per_ip', (string) $request->ip(), lockout: true),
        ]);

        RateLimiter::for(self::REGISTRATION, fn (Request $request) => $this->formLimit('registration', (string) $request->ip()));
        RateLimiter::for(self::PASSWORD_RESET, fn (Request $request) => $this->formLimit('password_reset', (string) $request->ip()));
        RateLimiter::for(self::VERIFICATION, fn (Request $request) => $this->limit('verification')->by($this->userKey($request)));
        RateLimiter::for(self::REAUTHENTICATION, fn (Request $request) => $this->limit('reauthentication')->by($this->userKey($request)));
        RateLimiter::for(self::EMAIL_CHANGE, fn (Request $request) => $this->limit('email_change')->by($this->userKey($request)));
    }

    private function limit(string $name): Limit
    {
        return Limit::perMinute((int) $this->config->get("headless.rate_limits.{$name}"));
    }

    /**
     * Public forms show the throttle message on the email field instead of an error page.
     */
    private function formLimit(string $name, string $key, bool $lockout = false): Limit
    {
        return $this->limit($name)->by($key)->response(function (Request $request, array $headers) use ($lockout): Response {
            if ($lockout) {
                event(new Lockout($request));
            }

            $message = __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60]);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_TOO_MANY_REQUESTS, $headers);
            }

            return back()->withInput($request->only(self::EMAIL_FIELD))->withErrors([self::EMAIL_FIELD => $message]);
        });
    }

    private function emailKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input(self::EMAIL_FIELD))).'|'.$request->ip();
    }

    private function userKey(Request $request): string
    {
        return (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }
}
