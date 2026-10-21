<?php

namespace Ssdev\Headless\Features\Roles\Console;

use Illuminate\Console\Command;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleRepository;

final class AssignRoleCommand extends Command
{
    protected $signature = 'headless:roles:assign
        {email : Email of the user}
        {role : Name of the role}';

    protected $description = 'Give a user a role';

    public function handle(UserRepository $users, RoleRepository $roles, RoleAssignmentService $assignments): int
    {
        $user = $users->findByEmail(mb_strtolower(trim((string) $this->argument('email'))));

        if ($user === null) {
            $this->components->error("No user has the email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        $role = $roles->findByName((string) $this->argument('role'));

        if ($role === null) {
            $this->components->error("No role is named [{$this->argument('role')}].");

            return self::FAILURE;
        }

        $assignments->assignWithoutActor($role, $user);

        $this->components->info("Gave [{$user->getAttribute('email')}] the [{$role->name}] role.");

        return self::SUCCESS;
    }
}
