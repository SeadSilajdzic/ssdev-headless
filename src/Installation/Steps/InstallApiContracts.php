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
 * Writes each contract test once: rerunning would regenerate its snapshots and erase the contract they guard.
 */
final class InstallApiContracts implements InstallStep
{
    private const DESCRIPTION = 'Install API contract tests';

    private const PACKAGE = 'ssdev/laravel-api-contracts:^1.3';

    private const PACKAGE_NAME = 'ssdev/laravel-api-contracts';

    private const TEST_DIRECTORY = 'tests/Feature';

    private const CORE_TEST = 'HeadlessApiContractTest.php';

    private const UPDATE_ENV = 'API_CONTRACT_UPDATE';

    public function __construct(private Composer $composer) {}

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;
        $pending = $this->pendingTests($context);

        if ($pending === []) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        // The core test exists once the contracts package is set up, so only a first install sets it up.
        if (isset($pending[self::TEST_DIRECTORY.'/'.self::CORE_TEST])) {
            if (! InstalledVersions::isInstalled(self::PACKAGE_NAME) && ! $this->composer->requirePackages([self::PACKAGE], true)) {
                return StepResult::manual(self::DESCRIPTION, 'Run: composer require --dev '.self::PACKAGE);
            }

            // New processes, because the package was not loaded when this command started.
            if (Process::path($files->path(''))->run($this->artisan('api:contract:install'))->failed()) {
                return StepResult::manual(self::DESCRIPTION, 'Run: php artisan api:contract:install');
            }
        }

        foreach ($pending as $path => $stub) {
            $files->put($path, Stub::get($stub));
        }

        $tests = implode(' ', array_keys($pending));
        $snapshots = Process::path($files->path(''))->env([self::UPDATE_ENV => '1'])->run($this->artisan('test '.$tests));

        if ($snapshots->failed()) {
            return StepResult::manual(self::DESCRIPTION, 'Run: '.self::UPDATE_ENV.'=1 php artisan test '.$tests);
        }

        return StepResult::changed(self::DESCRIPTION);
    }

    /**
     * @return array<string, string> test path => stub name, for the tests the project does not have yet
     */
    private function pendingTests(InstallContext $context): array
    {
        $tests = [self::CORE_TEST];

        foreach ($context->features as $feature) {
            $tests[] = $feature->apiContractTest();
        }

        $pending = [];

        foreach (array_filter($tests) as $test) {
            $path = self::TEST_DIRECTORY.'/'.$test;

            if (! $context->files->exists($path)) {
                $pending[$path] = $test;
            }
        }

        return $pending;
    }

    private function artisan(string $command): string
    {
        return escapeshellarg(PHP_BINARY).' artisan '.$command;
    }
}
