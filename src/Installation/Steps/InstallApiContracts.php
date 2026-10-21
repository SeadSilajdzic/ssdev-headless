<?php

namespace Ssdev\Headless\Installation\Steps;

use Composer\InstalledVersions;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Process;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;
use Ssdev\Headless\Installation\Stub;

/**
 * Runs once per project: rerunning would regenerate the snapshots and erase the contract they guard.
 */
final class InstallApiContracts implements InstallStep
{
    private const DESCRIPTION = 'Install API contract tests';

    private const PACKAGE = 'ssdev/laravel-api-contracts:^1.3';

    private const PACKAGE_NAME = 'ssdev/laravel-api-contracts';

    private const TEST_PATH = 'tests/Feature/HeadlessApiContractTest.php';

    private const UPDATE_ENV = 'API_CONTRACT_UPDATE';

    public function __construct(private Composer $composer) {}

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->exists(self::TEST_PATH)) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        if (! InstalledVersions::isInstalled(self::PACKAGE_NAME) && ! $this->composer->requirePackages([self::PACKAGE], true)) {
            return StepResult::manual(self::DESCRIPTION, 'Run: composer require --dev '.self::PACKAGE);
        }

        // New processes, because the package was not loaded when this command started.
        if (Process::path($files->path(''))->run($this->artisan('api:contract:install'))->failed()) {
            return StepResult::manual(self::DESCRIPTION, 'Run: php artisan api:contract:install');
        }

        $files->put(self::TEST_PATH, Stub::get('HeadlessApiContractTest.php'));

        $snapshots = Process::path($files->path(''))->env([self::UPDATE_ENV => '1'])->run($this->artisan('test '.self::TEST_PATH));

        if ($snapshots->failed()) {
            return StepResult::manual(self::DESCRIPTION, 'Run: '.self::UPDATE_ENV.'=1 php artisan test '.self::TEST_PATH);
        }

        return StepResult::changed(self::DESCRIPTION);
    }

    private function artisan(string $command): string
    {
        return escapeshellarg(PHP_BINARY).' artisan '.$command;
    }
}
