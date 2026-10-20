<?php

namespace Ssdev\Headless\Tests\Fixtures\Features;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class FakeInstallStep implements InstallStep
{
    public function handle(InstallContext $context): StepResult
    {
        $context->files->put('fake-step.txt', 'done');

        return StepResult::changed('Fake step');
    }
}
