<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class ConfigureExceptionRendering implements InstallStep
{
    private const DESCRIPTION = 'Render error pages with Inertia';

    private const PATH = 'bootstrap/app.php';

    private const ANCHOR = '/->withExceptions\(function \(Exceptions \$exceptions\)(?:: void)? \{\R/';

    private const REGISTRATION = "        \\Ssdev\\Headless\\Exceptions\\InertiaErrorRenderer::register(\$exceptions);\n";

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains(self::PATH, 'InertiaErrorRenderer')) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        if (! $files->insertAfterPattern(self::PATH, self::ANCHOR, self::REGISTRATION)) {
            return StepResult::manual(
                self::DESCRIPTION,
                "Add to ->withExceptions() in bootstrap/app.php:\n".self::REGISTRATION,
            );
        }

        return StepResult::changed(self::DESCRIPTION);
    }
}
