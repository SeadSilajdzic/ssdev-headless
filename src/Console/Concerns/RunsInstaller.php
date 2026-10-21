<?php

namespace Ssdev\Headless\Console\Concerns;

use Illuminate\Support\Facades\Process;
use Ssdev\Headless\Installation\StepResult;
use Ssdev\Headless\Installation\StepStatus;

use function Laravel\Prompts\confirm;

/**
 * What headless:install and headless:add share around the installer run.
 */
trait RunsInstaller
{
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
