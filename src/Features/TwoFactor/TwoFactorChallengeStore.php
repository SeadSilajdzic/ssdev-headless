<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;

/**
 * API sign-in challenges live in the cache only as a hash of their token, until they are answered or expire.
 */
final class TwoFactorChallengeStore
{
    private const CACHE_PREFIX = 'headless.two-factor.challenge.';

    private const TOKEN_LENGTH = 40;

    public function __construct(
        private Cache $cache,
        private Config $config,
    ) {}

    public function create(User $user, string $deviceName): PendingTwoFactorChallenge
    {
        $token = Str::random(self::TOKEN_LENGTH);
        $expiresAt = CarbonImmutable::now()->addMinutes((int) $this->config->get('headless.two_factor.challenge_expiration'));

        $this->cache->put($this->key($token), ['user_id' => $user->getKey(), 'device_name' => $deviceName], $expiresAt);

        return new PendingTwoFactorChallenge($token, $expiresAt);
    }

    /**
     * @return array{user_id: int|string, device_name: string}|null
     */
    public function find(#[\SensitiveParameter] string $token): ?array
    {
        $challenge = $this->cache->get($this->key($token));

        return is_array($challenge) ? $challenge : null;
    }

    public function forget(#[\SensitiveParameter] string $token): void
    {
        $this->cache->forget($this->key($token));
    }

    private function key(string $token): string
    {
        return self::CACHE_PREFIX.hash('sha256', $token);
    }
}
