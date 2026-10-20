<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

final class GrantPermissionsCommand extends Command
{
    protected $signature = 'headless:permissions:grant
        {email : Email of the user}
        {permissions?* : Permission names}
        {--all : Grant every registered permission}';

    protected $description = 'Grant permissions directly to a user';

    public function handle(PermissionRegistry $registry, PermissionSynchronizer $synchronizer): int
    {
        $user = $this->findUser((string) $this->argument('email'));

        if ($user === null) {
            $this->components->error("No user has the email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        if (! method_exists($user, 'givePermissionTo')) {
            $this->components->error('Add the Spatie\Permission\Traits\HasRoles trait to your User model.');

            return self::FAILURE;
        }

        /** @var list<string> $permissions */
        $permissions = $this->option('all') ? $registry->names() : $this->argument('permissions');
        $unknown = array_diff($permissions, $registry->names());

        if ($permissions === [] || $unknown !== []) {
            $this->components->error($unknown === [] ? 'Pass permission names or --all.' : 'Unknown permissions: '.implode(', ', $unknown).'.');

            return self::FAILURE;
        }

        $synchronizer->sync();
        $user->givePermissionTo($permissions);

        $this->components->info('Granted '.implode(', ', $permissions).'.');

        return self::SUCCESS;
    }

    private function findUser(string $email): ?Model
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model');

        return $model::query()->where('email', $email)->first();
    }
}
