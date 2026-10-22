<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Features\Tenancy\Events\TenantCreated;

final class TenantService
{
    public function __construct(
        private ConnectionInterface $database,
        private TenantOwnerService $owners,
        private Dispatcher $events,
    ) {}

    public function create(User $actor, string $name, string $subdomain, string $ownerEmail): Tenant
    {
        try {
            $tenant = $this->database->transaction(function () use ($name, $subdomain, $ownerEmail): Tenant {
                $tenant = Tenant::query()->forceCreate(['name' => $name, 'subdomain' => mb_strtolower(trim($subdomain))]);
                $this->owners->createOwner($tenant, $ownerEmail);

                return $tenant;
            });
        } catch (UniqueConstraintViolationException) {
            // A parallel request took the subdomain after this one's validation passed.
            throw ValidationException::withMessages(['subdomain' => __('headless::tenancy.errors.subdomain_taken')]);
        }

        $this->events->dispatch(new TenantCreated($tenant, $actor));

        return $tenant;
    }

    public function rename(Tenant $tenant, string $name): void
    {
        $tenant->forceFill(['name' => $name])->save();
    }
}
