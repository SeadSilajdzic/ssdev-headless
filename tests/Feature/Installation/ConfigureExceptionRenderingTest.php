<?php

use Ssdev\Headless\Installation\Steps\ConfigureExceptionRendering;
use Ssdev\Headless\Installation\StepStatus;

it('registers the inertia error renderer in bootstrap/app.php', function () {
    $files = fixtureProject();

    expect(app(ConfigureExceptionRendering::class)->handle(installContext($files))->status)->toBe(StepStatus::Changed)
        ->and($files->get('bootstrap/app.php'))->toContain("->withExceptions(function (Exceptions \$exceptions): void {\n        \\Ssdev\\Headless\\Exceptions\\InertiaErrorRenderer::register(\$exceptions);\n");
});

it('skips when the renderer is already registered', function () {
    $files = fixtureProject();
    app(ConfigureExceptionRendering::class)->handle(installContext($files));
    $bootstrap = $files->get('bootstrap/app.php');

    expect(app(ConfigureExceptionRendering::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('bootstrap/app.php'))->toBe($bootstrap);
});

it('returns manual instructions when bootstrap/app.php has no exceptions anchor', function () {
    $files = fixtureProject();
    $files->put('bootstrap/app.php', "<?php\n\nreturn require 'custom.php';\n");

    $result = app(ConfigureExceptionRendering::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('InertiaErrorRenderer::register($exceptions);')
        ->and($files->get('bootstrap/app.php'))->toBe("<?php\n\nreturn require 'custom.php';\n");
});

it('registers the api error renderer next to the inertia one', function () {
    $files = fixtureProject();

    app(ConfigureExceptionRendering::class)->handle(installContext($files));
    $bootstrap = $files->get('bootstrap/app.php');

    expect(substr_count($bootstrap, '\Ssdev\Headless\Api\ApiErrorRenderer::register($exceptions);'))->toBe(1)
        ->and(substr_count($bootstrap, 'InertiaErrorRenderer::register($exceptions);'))->toBe(1);
});

it('adds the api error renderer to projects installed before the api', function () {
    $files = fixtureProject();
    $files->put('bootstrap/app.php', str_replace(
        "->withExceptions(function (Exceptions \$exceptions): void {\n",
        "->withExceptions(function (Exceptions \$exceptions): void {\n        \Ssdev\Headless\Exceptions\InertiaErrorRenderer::register(\$exceptions);\n",
        $files->get('bootstrap/app.php'),
    ));

    expect(app(ConfigureExceptionRendering::class)->handle(installContext($files))->status)->toBe(StepStatus::Changed)
        ->and(substr_count($files->get('bootstrap/app.php'), 'ApiErrorRenderer::register($exceptions);'))->toBe(1)
        ->and(substr_count($files->get('bootstrap/app.php'), 'InertiaErrorRenderer::register($exceptions);'))->toBe(1);
});
