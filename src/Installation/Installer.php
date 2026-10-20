<?php

namespace Ssdev\Headless\Installation;

use Illuminate\Contracts\Container\Container;
use Ssdev\Headless\Installation\Steps\AddNpmDependencies;
use Ssdev\Headless\Installation\Steps\ConfigureExceptionRendering;
use Ssdev\Headless\Installation\Steps\InstallFrontendScaffolding;
use Ssdev\Headless\Installation\Steps\InstallInertiaMiddleware;
use Ssdev\Headless\Installation\Steps\PublishConfig;
use Ssdev\Headless\Installation\Steps\PublishMigrations;
use Ssdev\Headless\Installation\Steps\RequireComposerPackages;

final class Installer
{
    public const BASE_STEPS = [
        PublishConfig::class,
        PublishMigrations::class,
        InstallInertiaMiddleware::class,
        ConfigureExceptionRendering::class,
        InstallFrontendScaffolding::class,
        AddNpmDependencies::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * @return list<StepResult>
     */
    public function run(InstallContext $context): array
    {
        return array_map(
            fn (string $step) => $this->resolve($step)->handle($context),
            $this->steps($context),
        );
    }

    /**
     * @return list<class-string<InstallStep>>
     */
    private function steps(InstallContext $context): array
    {
        $steps = [RequireComposerPackages::class, ...self::BASE_STEPS];

        foreach ($context->features as $feature) {
            array_push($steps, ...$feature->installSteps());
        }

        return $steps;
    }

    /**
     * @param  class-string<InstallStep>  $step
     */
    private function resolve(string $step): InstallStep
    {
        return $this->container->make($step);
    }
}
