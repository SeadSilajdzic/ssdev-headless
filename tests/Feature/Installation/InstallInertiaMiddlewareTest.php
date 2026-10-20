<?php

use Ssdev\Headless\Installation\Steps\InstallInertiaMiddleware;
use Ssdev\Headless\Installation\StepStatus;

it('registers the inertia middleware in bootstrap/app.php', function () {
    $files = fixtureProject();

    expect(app(InstallInertiaMiddleware::class)->handle(installContext($files))->status)->toBe(StepStatus::Changed)
        ->and($files->get('bootstrap/app.php'))->toContain("\$middleware->web(append: [\n            \\App\\Http\\Middleware\\HandleInertiaRequests::class,")
        ->and($files->get('app/Http/Middleware/HandleInertiaRequests.php'))->toContain('extends HandleHeadlessInertiaRequests');
});

it('skips when the middleware is already registered', function () {
    $files = fixtureProject();
    app(InstallInertiaMiddleware::class)->handle(installContext($files));
    $bootstrap = $files->get('bootstrap/app.php');

    expect(app(InstallInertiaMiddleware::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('bootstrap/app.php'))->toBe($bootstrap);
});

it('returns manual instructions when bootstrap/app.php has no middleware anchor', function () {
    $files = fixtureProject();
    $files->put('bootstrap/app.php', "<?php\n\nreturn require 'custom.php';\n");

    $result = app(InstallInertiaMiddleware::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('HandleInertiaRequests::class')
        ->and($files->get('bootstrap/app.php'))->toBe("<?php\n\nreturn require 'custom.php';\n");
});

it('keeps an existing custom middleware unless overwriting is allowed', function () {
    $files = fixtureProject();
    $files->put('app/Http/Middleware/HandleInertiaRequests.php', '<?php // custom');

    expect(app(InstallInertiaMiddleware::class)->handle(installContext($files))->status)->toBe(StepStatus::Manual)
        ->and($files->get('app/Http/Middleware/HandleInertiaRequests.php'))->toBe('<?php // custom');

    app(InstallInertiaMiddleware::class)->handle(installContext($files, overwrite: true));

    expect($files->get('app/Http/Middleware/HandleInertiaRequests.php'))->toContain('extends HandleHeadlessInertiaRequests');
});
