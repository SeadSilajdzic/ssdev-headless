<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User;

final class SessionRepository
{
    private const DATABASE_DRIVER = 'database';

    public function __construct(
        private Repository $config,
        private DatabaseManager $database,
    ) {}

    public function supportsBrowserSessions(): bool
    {
        return $this->config->get('session.driver') === self::DATABASE_DRIVER;
    }

    /**
     * Other drivers keep no sessions table, so there is nothing to delete.
     */
    public function deleteForUser(User $user, ?string $exceptSessionId = null): int
    {
        if (! $this->supportsBrowserSessions()) {
            return 0;
        }

        return $this->table()
            ->where('user_id', $user->getAuthIdentifier())
            ->when($exceptSessionId !== null, fn (Builder $query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    private function table(): Builder
    {
        return $this->database
            ->connection($this->config->get('session.connection'))
            ->table((string) $this->config->get('session.table', 'sessions'));
    }
}
