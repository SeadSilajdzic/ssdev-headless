<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Ssdev\Headless\Console\Concerns\RunsInstaller;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\UnknownFeatureException;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\Installer;
use Ssdev\Headless\Installation\ProjectFiles;

final class AddCommand extends Command
{
    use RunsInstaller;

    protected $signature = 'headless:add
        {feature : Feature key, e.g. two-factor}
        {--force : Overwrite changed files without asking}
        {--migrate : Run the database migrations and sync permissions}
        {--npm : Run npm install and npm run build}';

    protected $description = 'Add a feature to a project that already has the headless core';

    public function handle(FeatureRegistry $registry, Installer $installer, ProjectFiles $files): int
    {
        $key = (string) $this->argument('feature');
        $feature = $registry->find($key);

        if ($feature === null) {
            $available = implode(', ', array_map(fn (FeatureModule $module) => $module->key(), $registry->available()));
            $this->components->error(UnknownFeatureException::forKey($key)->getMessage()." Available features: {$available}.");

            return self::FAILURE;
        }

        // The same steps as headless:install, so features installed before stay complete as well.
        $features = collect([...$registry->enabled(), $feature])->unique(fn (FeatureModule $module) => $module->key())->values()->all();

        $this->report($installer->run(new InstallContext($files, $features, $this->overwriteConfirmation(...))));
        $this->runMigrations();

        return $this->runNpm() ? self::SUCCESS : self::FAILURE;
    }
}
