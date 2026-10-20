<?php

use Ssdev\Headless\Installation\Steps\InstallFrontendScaffolding;
use Ssdev\Headless\Installation\StepStatus;

it('replaces the default frontend entry points when overwriting is allowed', function () {
    $files = fixtureProject();

    expect(app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true))->status)->toBe(StepStatus::Changed)
        ->and($files->exists('vite.config.js'))->toBeFalse()
        ->and($files->get('vite.config.ts'))->toContain('headless()')
        ->and($files->exists('resources/js/app.js'))->toBeFalse()
        ->and($files->get('resources/js/app.ts'))->toContain('createHeadlessApp')
        ->and($files->get('tsconfig.json'))->toContain('"@headless/*"');
});

it('leaves a customized vite.config.js alone unless overwriting is allowed', function () {
    $files = fixtureProject();
    $files->put('vite.config.js', '// custom');

    expect(app(InstallFrontendScaffolding::class)->handle(installContext($files))->status)->toBe(StepStatus::Manual)
        ->and($files->get('vite.config.js'))->toBe('// custom')
        ->and($files->exists('vite.config.ts'))->toBeFalse();

    app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true));

    expect($files->exists('vite.config.js'))->toBeFalse()
        ->and($files->get('vite.config.ts'))->toContain('headless()');
});

it('keeps a customized app.js unless overwriting is allowed', function () {
    $files = fixtureProject();
    $files->put('resources/js/app.js', "import './custom';\n");

    app(InstallFrontendScaffolding::class)->handle(installContext($files));

    expect($files->get('resources/js/app.js'))->toBe("import './custom';\n")
        ->and($files->get('resources/js/app.ts'))->toContain('createHeadlessApp');
});

it('imports the headless stylesheet and removes the default font theme', function () {
    $files = fixtureProject();

    app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true));

    expect($files->get('resources/css/app.css'))
        ->toContain("@import 'tailwindcss';\n@import '../../vendor/ssdev/laravel-headless/resources/css/headless.css';")
        ->not->toContain('Instrument Sans');
});

it('returns manual instructions when the stylesheet has no tailwind import', function () {
    $files = fixtureProject();
    $files->put('resources/css/app.css', "body {}\n");

    $result = app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('headless.css')
        ->and($files->get('resources/css/app.css'))->toBe("body {}\n");
});

it('returns manual instructions when tsconfig.json lacks the headless path alias', function () {
    $files = fixtureProject();
    $files->put('tsconfig.json', "{}\n");

    $result = app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('@headless/*')
        ->and($files->get('tsconfig.json'))->toBe("{}\n");
});

it('skips when the frontend is already scaffolded', function () {
    $files = fixtureProject();
    app(InstallFrontendScaffolding::class)->handle(installContext($files, overwrite: true));
    $css = $files->get('resources/css/app.css');

    expect(app(InstallFrontendScaffolding::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('resources/css/app.css'))->toBe($css);
});
