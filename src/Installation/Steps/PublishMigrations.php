<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\MigrationPublisher;
use Ssdev\Headless\Installation\StepResult;

final class PublishMigrations implements InstallStep
{
    private const DESCRIPTION = 'Publish database migrations';

    public function __construct(private MigrationPublisher $publisher) {}

    public function handle(InstallContext $context): StepResult
    {
        if ($this->publisher->publish($context->files) === []) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        return StepResult::changed(self::DESCRIPTION);
    }
}
