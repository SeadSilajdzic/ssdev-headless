<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Notifications\Dispatcher as Notifications;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Tenancy\Notifications\TenantOwnerInvitation;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

/**
 * The owner is the tenant's first account and holds every permission of the tenant, so it can manage the rest itself.
 * The invitation is a password broker token of its own, so it works once, expires and fits only the owner's email.
 */
final class TenantOwnerService
{
    public const BROKER = 'headless_tenant_owners';

    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private PermissionRegistry $permissions,
        private PermissionSynchronizer $synchronizer,
        private PasswordBrokerFactory $brokers,
        private Notifications $notifications,
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
     * Sends a new link to the owner; it replaces any earlier one.
     */
    public function invite(Tenant $tenant): string
    {
        return (string) $tenant->execute(function () use ($tenant): string {
            $owner = $this->users->find((int) $tenant->owner_id);

            return $owner === null ? PasswordBroker::INVALID_USER : $this->broker()->sendResetLink(
                ['email' => $owner->getAttribute('email')],
                function (CanResetPassword $owner, string $token) use ($tenant): void {
                    $url = route('headless.tenant-owner.show', ['token' => $token, 'email' => $owner->getEmailForPasswordReset()]);
                    $this->notifications->send($owner, new TenantOwnerInvitation($tenant->name, $url));
                },
            );
        });
    }

    public function resend(Tenant $tenant): void
    {
        if ($this->owner($tenant)?->getAttribute('email_verified_at') !== null) {
            throw ValidationException::withMessages(['owner' => __('headless::tenancy.errors.owner_accepted')]);
        }

        if ($this->invite($tenant) === PasswordBroker::RESET_THROTTLED) {
            throw ValidationException::withMessages(['owner' => __('headless::tenancy.errors.owner_invitation_throttled')]);
        }
    }

    /**
     * Runs on the tenant's host, so the owner is looked up inside that tenant only.
     */
    public function accept(string $token, string $email, string $name, #[\SensitiveParameter] string $password): void
    {
        $email = mb_strtolower(trim($email));
        $owner = $this->users->findByEmail($email);

        // Once the owner confirmed the address, a link still in the inbox must not set the password again.
        if ($owner === null || $owner->getAttribute('email_verified_at') !== null) {
            throw ValidationException::withMessages(['email' => __('headless::tenancy.errors.owner_invitation_invalid')]);
        }

        $status = $this->broker()->reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $owner, string $password) use ($name): void {
                $owner->forceFill([
                    'name' => $name,
                    'password' => $this->hasher->make($password),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('headless::tenancy.errors.owner_invitation_invalid')]);
        }
    }

    /**
     * @return list<string>
     */
    private function tenantPermissions(): array
    {
        return array_values(array_diff($this->permissions->names(), TenantPermission::names()));
    }

    private function broker(): PasswordBroker
    {
        return $this->brokers->broker(self::BROKER);
    }
}
