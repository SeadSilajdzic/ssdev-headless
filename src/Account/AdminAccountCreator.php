<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

final class AdminAccountCreator
{
    public function __construct(
        private UserRepository $users,
        private PermissionRegistry $permissions,
        private PermissionSynchronizer $synchronizer,
        private Hasher $hasher,
        private ConnectionInterface $database,
    ) {}

    /**
     * The first admin gets every registered permission directly; the roles feature later assigns them through a role.
     */
    public function create(string $name, string $email, #[\SensitiveParameter] string $password): User
    {
        return $this->database->transaction(function () use ($name, $email, $password): User {
            $admin = $this->users->create([
                'name' => $name,
                'email' => $email,
                'password' => $this->hasher->make($password),
                'email_verified_at' => now(),
            ]);

            $this->synchronizer->sync();

            if (method_exists($admin, 'givePermissionTo') && $this->permissions->names() !== []) {
                $admin->givePermissionTo($this->permissions->names());
            }

            return $admin;
        });
    }
}
