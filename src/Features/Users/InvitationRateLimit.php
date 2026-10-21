<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class InvitationRateLimit
{
    public const NAME = 'headless.invitations';

    public function __construct(private Repository $config) {}

    public function register(): void
    {
        RateLimiter::for(self::NAME, fn (Request $request) => Limit::perMinute((int) $this->config->get('headless.rate_limits.invitations'))
            ->by('user|'.$request->user()?->getAuthIdentifier()));
    }
}
