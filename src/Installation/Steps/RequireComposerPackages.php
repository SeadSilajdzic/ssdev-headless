<?php

namespace Ssdev\Headless\Installation\Steps;

use Composer\InstalledVersions;
use Illuminate\Support\Composer;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class RequireComposerPackages implements InstallStep
{
    private const DESCRIPTION = 'Require composer packages';

    public function __construct(private Composer $composer) {}

    public function handle(InstallContext $context): StepResult
    {
        $packages = $this->missingPackages($context);

        if ($packages === []) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        if (! $this->composer->requirePackages($packages)) {
            return StepResult::manual(self::DESCRIPTION, 'Run: composer require '.implode(' ', $packages));
        }

        return StepResult::changed(self::DESCRIPTION);
    }

    /**
     * @return list<string>
     */
    private function missingPackages(InstallContext $context): array
    {
        $packages = [];

        foreach ($context->features as $feature) {
            foreach ($feature->composerPackages() as $name => $constraint) {
                if (! InstalledVersions::isInstalled($name)) {
                    $packages[$name] = "{$name}:{$constraint}";
                }
            }
        }

        return array_values($packages);
    }
}
