<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;
use Ssdev\Headless\Installation\Stub;

final class InstallInertiaMiddleware implements InstallStep
{
    private const DESCRIPTION = 'Register the Inertia middleware';

    private const MIDDLEWARE_PATH = 'app/Http/Middleware/HandleInertiaRequests.php';

    private const BOOTSTRAP_PATH = 'bootstrap/app.php';

    private const ANCHOR = '/->withMiddleware\(function \(Middleware \$middleware\)(?:: void)? \{\R/';

    private const REGISTRATION = <<<'PHP'
                $middleware->web(append: [
                    \App\Http\Middleware\HandleInertiaRequests::class,
                    \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
                ]);

        PHP;

    public function handle(InstallContext $context): StepResult
    {
        return StepResult::combine(self::DESCRIPTION, [
            $this->publishMiddleware($context),
            $this->registerMiddleware($context),
        ]);
    }

    private function publishMiddleware(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains(self::MIDDLEWARE_PATH, 'HandleHeadlessInertiaRequests')) {
            return StepResult::skipped(self::MIDDLEWARE_PATH);
        }

        if ($files->exists(self::MIDDLEWARE_PATH) && ! $context->mayOverwrite(self::MIDDLEWARE_PATH)) {
            return StepResult::manual(
                self::MIDDLEWARE_PATH,
                'Make App\Http\Middleware\HandleInertiaRequests extend Ssdev\Headless\Http\Middleware\HandleHeadlessInertiaRequests.',
            );
        }

        $files->put(self::MIDDLEWARE_PATH, Stub::get('HandleInertiaRequests.php'));

        return StepResult::changed(self::MIDDLEWARE_PATH);
    }

    private function registerMiddleware(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains(self::BOOTSTRAP_PATH, 'HandleInertiaRequests::class')) {
            return StepResult::skipped(self::BOOTSTRAP_PATH);
        }

        if (! $files->insertAfterPattern(self::BOOTSTRAP_PATH, self::ANCHOR, self::REGISTRATION)) {
            return StepResult::manual(
                self::BOOTSTRAP_PATH,
                "Add to ->withMiddleware() in bootstrap/app.php:\n".self::REGISTRATION,
            );
        }

        return StepResult::changed(self::BOOTSTRAP_PATH);
    }
}
