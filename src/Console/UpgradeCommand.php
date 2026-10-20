<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Ssdev\Headless\Installation\MigrationPublisher;
use Ssdev\Headless\Installation\ProjectFiles;

final class UpgradeCommand extends Command
{
    protected $signature = 'headless:upgrade';

    protected $description = 'Publish migrations added by a newer version of the package';

    public function handle(MigrationPublisher $publisher, ProjectFiles $files): int
    {
        $published = $publisher->publish($files);

        if ($published === []) {
            $this->components->info('Migrations are up to date.');

            return self::SUCCESS;
        }

        $this->components->info('Published migrations:');
        $this->components->bulletList($published);
        $this->components->warn('Run [php artisan migrate] to apply them, and check UPGRADE.md for other changes.');

        return self::SUCCESS;
    }
}
