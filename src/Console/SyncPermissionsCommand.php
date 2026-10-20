<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'headless:permissions:sync';

    protected $description = 'Create the permissions registered by the package, its features and the application';

    public function handle(PermissionSynchronizer $synchronizer): int
    {
        $created = $synchronizer->sync();

        $this->components->info("Created {$created} ".Str::plural('permission', $created).'.');

        return self::SUCCESS;
    }
}
