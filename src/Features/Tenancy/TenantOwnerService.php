<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

/**
 * The owner is the tenant's first account and holds every permission of the tenant, so it can manage the rest itself.
 */
final class TenantOwnerService
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private PermissionRegistry $permissions,
        private PermissionSynchronizer $synchronizer,
    ) {}

    public function createOwner(Tenant $tenant, string $email): User
    {
        $email = mb_strtolower(trim($email));
        $this->synchronizer->sync();

        $owner = $tenant->execute(function () use ($email): User {
            $owner = $this->users->create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                // Nobody knows this password; the owner chooses their own through the invitation.
                'password' => $this->hasher->make(Str::random(64)),
            ]);

            // Directly rather than through a role: until roles are kept per tenant, a role would be shared with the central level.
            if (method_exists($owner, 'givePermissionTo') && $this->tenantPermissions() !== []) {
                $owner->givePermissionTo($this->tenantPermissions());
            }

            return $owner;
        });

        $tenant->forceFill(['owner_id' => $owner->getKey()])->save();

        return $owner;
    }

    public function owner(Tenant $tenant): ?User
    {
        return $tenant->owner_id === null ? null : $tenant->execute(fn (): ?User => $this->users->find($tenant->owner_id));
    }

    /**
     * @return list<string>
     */
    private function tenantPermissions(): array
    {
        return array_values(array_diff($this->permissions->names(), TenantPermission::names()));
    }
}
