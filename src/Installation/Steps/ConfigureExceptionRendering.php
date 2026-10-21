<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class ConfigureExceptionRendering implements InstallStep
{
    private const DESCRIPTION = 'Configure error rendering';

    private const PATH = 'bootstrap/app.php';

    private const ANCHOR = '/->withExceptions\(function \(Exceptions \$exceptions\)(?:: void)? \{\R/';

    /**
     * Class name => line to register it, in the order they belong in withExceptions().
     */
    private const REGISTRATIONS = [
        'InertiaErrorRenderer' => "        \\Ssdev\\Headless\\Exceptions\\InertiaErrorRenderer::register(\$exceptions);\n",
        'ApiErrorRenderer' => "        \\Ssdev\\Headless\\Api\\ApiErrorRenderer::register(\$exceptions);\n",
    ];

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;
        $missing = implode('', array_filter(
            self::REGISTRATIONS,
            fn (string $class) => ! $files->contains(self::PATH, $class.'::register'),
            ARRAY_FILTER_USE_KEY,
        ));

        if ($missing === '') {
            return StepResult::skipped(self::DESCRIPTION);
        }

        if (! $files->insertAfterPattern(self::PATH, self::ANCHOR, $missing)) {
            return StepResult::manual(self::DESCRIPTION, "Add to ->withExceptions() in bootstrap/app.php:\n".$missing);
        }

        return StepResult::changed(self::DESCRIPTION);
    }
}
