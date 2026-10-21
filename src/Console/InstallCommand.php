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

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

final class InstallCommand extends Command
{
    use RunsInstaller;

    protected $signature = 'headless:install
        {--features= : Comma-separated feature keys}
        {--force : Overwrite changed files without asking}
        {--migrate : Run the database migrations and sync permissions}
        {--npm : Run npm install and npm run build}';

    protected $description = 'Install the headless core and the selected features';

    public function handle(FeatureRegistry $registry, Installer $installer, ProjectFiles $files): int
    {
        try {
            $features = $this->selectedFeatures($registry);
        } catch (UnknownFeatureException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $results = $installer->run(new InstallContext($files, $features, $this->overwriteConfirmation(...)));

        $this->report($results);

        if ($this->runMigrations()) {
            $this->offerFirstAdmin();
        }

        return $this->runNpm() ? self::SUCCESS : self::FAILURE;
    }

    private function offerFirstAdmin(): void
    {
        if ($this->input->isInteractive() && confirm('Create the first admin account now?', default: true)) {
            $this->call('headless:create-admin');
        }
    }

    /**
     * Features that are already installed stay in the run, so their steps can fill in anything missing.
     *
     * @return list<FeatureModule>
     */
    private function selectedFeatures(FeatureRegistry $registry): array
    {
        $option = $this->option('features');
        $installed = array_map(fn (FeatureModule $module) => $module->key(), $registry->enabled());

        if (is_string($option) && $option !== '') {
            $keys = array_values(array_filter(array_map('trim', explode(',', $option))));
        } elseif ($this->input->isInteractive()) {
            $keys = multiselect(
                label: 'Which features would you like to install?',
                options: collect($registry->available())
                    ->mapWithKeys(fn (FeatureModule $module) => [$module->key() => $module->label()])
                    ->all(),
                default: $installed,
                hint: 'The core (authentication, profile, security, API tokens) is always installed, and so are features installed before.',
            );
        } else {
            $keys = [];
        }

        return array_map(
            fn (string $key) => $registry->find($key) ?? throw UnknownFeatureException::forKey($key),
            array_values(array_unique([...$installed, ...$keys])),
        );
    }
}
