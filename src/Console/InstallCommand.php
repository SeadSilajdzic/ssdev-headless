<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\UnknownFeatureException;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\Installer;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Installation\StepResult;
use Ssdev\Headless\Installation\StepStatus;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

final class InstallCommand extends Command
{
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

    private function runMigrations(): bool
    {
        $shouldRun = $this->option('migrate')
            || ($this->input->isInteractive() && confirm('Run database migrations now?', default: true));

        if (! $shouldRun) {
            return false;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('headless:permissions:sync');

        return true;
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

    private function overwriteConfirmation(string $path): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->input->isInteractive() && confirm("Overwrite {$path}?", default: false);
    }

    /**
     * @param  list<StepResult>  $results
     */
    private function report(array $results): void
    {
        $this->table(['Step', 'Status'], array_map(
            fn (StepResult $result) => [$result->description, $result->status->value],
            $results,
        ));

        foreach ($results as $result) {
            if ($result->status === StepStatus::Manual) {
                $this->components->warn("{$result->description}: {$result->instructions}");
            }
        }
    }

    private function runNpm(): bool
    {
        $shouldRun = $this->option('npm')
            || ($this->input->isInteractive() && confirm('Run npm install and npm run build now?', default: true));

        if (! $shouldRun) {
            return true;
        }

        foreach (['npm install', 'npm run build'] as $command) {
            $result = Process::path(base_path())->forever()->run($command, fn (string $type, string $output) => $this->output->write($output));

            if ($result->failed()) {
                $this->components->error("[{$command}] failed.");

                return false;
            }
        }

        return true;
    }
}
