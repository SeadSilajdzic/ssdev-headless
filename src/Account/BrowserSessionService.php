<?php

namespace Ssdev\Headless\Account;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Auth\User;

/**
 * Raw session ids never leave this class: the frontend only sees a keyed hash of them.
 */
final class BrowserSessionService
{
    public function __construct(
        private SessionRepository $sessions,
        private Repository $config,
    ) {}

    /**
     * @return list<BrowserSession>
     */
    public function forUser(User $user, string $currentSessionId): array
    {
        return $this->sessions->forUser($user)->map(function (object $session) use ($currentSessionId): BrowserSession {
            $agent = new UserAgent($session->user_agent);

            return new BrowserSession(
                $this->publicId($session->id),
                $agent->platform(),
                $agent->browser(),
                $session->ip_address,
                CarbonImmutable::createFromTimestamp($session->last_activity),
                hash_equals($session->id, $currentSessionId),
            );
        })->values()->all();
    }

    public function logout(User $user, string $publicId): bool
    {
        $session = $this->sessions->forUser($user)->first(fn (object $session) => hash_equals($this->publicId($session->id), $publicId));

        if ($session === null) {
            return false;
        }

        $this->sessions->delete($session->id);

        return true;
    }

    public function logoutOthers(User $user, string $currentSessionId): int
    {
        return $this->sessions->deleteForUser($user, $currentSessionId);
    }

    public function publicId(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) $this->config->get('app.key'));
    }
}
