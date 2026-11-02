<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Account\SessionRepository;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleted;
use Ssdev\Headless\Features\Tenancy\Events\TenantDeleting;
use Ssdev\Headless\Support\Isolation;

final class TenantDeletionService
{
    private const CHUNK_SIZE = 100;

    public function __construct(
        private DatabaseManager $database,
        private SessionRepository $sessions,
        private PersonalAccessTokenService $tokens,
        private Isolation $isolation,
        private Factory $storage,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function delete(User $actor, Tenant $tenant): void
    {
        $tenantId = (int) $tenant->getKey();
        $subdomain = $tenant->subdomain;
        $tenantDirectory = (string) $tenant->execute(fn (): string => $this->isolation->path(''));

        $this->database->connection()->transaction(function () use ($tenant): void {
            $tenant->execute(function (Tenant $tenant): void {
                $this->events->dispatch(new TenantDeleting($tenant));
                $this->deleteAccounts($tenant);
            });

            $this->deleteBrokerTokens($tenant);
            $tenant->delete();
        });

        // Removed only after the commit, so a failed deletion never leaves accounts without their files.
        foreach ($this->disks() as $disk) {
            $this->storage->disk($disk)->deleteDirectory($tenantDirectory);
        }

        $this->events->dispatch(new TenantDeleted($tenantId, $subdomain, $actor));
    }

    private function deleteAccounts(Tenant $tenant): void
    {
        foreach ($this->accounts($tenant)->lazyById(self::CHUNK_SIZE) as $account) {
            $this->sessions->deleteForUser($account);
            $this->tokens->revokeAll($account);
            // A force delete also detaches the account's roles and permissions.
            $account->forceDelete();
        }
    }

    /**
     * @return Builder<User>
     */
    private function accounts(Tenant $tenant): Builder
    {
        /** @var class-string<User> $model */
        $model = $this->config->get('auth.providers.users.model');

        // Soft deleted accounts too; the explicit tenant keeps central accounts out whatever scopes the model has.
        return $model::query()->withoutGlobalScopes()->where(TenantContext::COLUMN, $tenant->getKey());
    }

    /**
     * @return list<string>
     */
    private function disks(): array
    {
        return array_values(array_unique([
            (string) $this->config->get('headless.avatars.disk'),
            (string) $this->config->get('filesystems.default'),
            ...(array) $this->config->get('headless.tenancy.disks', []),
        ]));
    }

    /**
     * Brokers keep a tenant's tokens under keys that start with "<tenant>|" (TenantResetIdentity).
     */
    private function deleteBrokerTokens(Tenant $tenant): void
    {
        foreach ((array) $this->config->get('auth.passwords', []) as $broker) {
            if (isset($broker['table'])) {
                $this->database->connection($broker['connection'] ?? null)
                    ->table($broker['table'])
                    ->where('email', 'like', $tenant->getKey().'|%')
                    ->delete();
            }
        }
    }
}
