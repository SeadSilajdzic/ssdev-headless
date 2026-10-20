<?php

use Ssdev\Headless\Installation\Steps\AddNpmDependencies;
use Ssdev\Headless\Installation\StepStatus;

it('adds missing npm dependencies without changing existing versions', function () {
    $files = fixtureProject();
    $package = $files->getJson('package.json');
    $package['devDependencies']['vue'] = '^3.4.0';
    $files->putJson('package.json', $package);

    expect(app(AddNpmDependencies::class)->handle(installContext($files))->status)->toBe(StepStatus::Changed);
    $devDependencies = $files->getJson('package.json')['devDependencies'];

    expect($devDependencies['vue'])->toBe('^3.4.0')
        ->and($devDependencies['reka-ui'])->toBe('^2.11')
        ->and($files->getJson('package.json')['scripts']['typecheck'])->toBe('vue-tsc --noEmit');
});

it('skips when every dependency is already present', function () {
    $files = fixtureProject();
    app(AddNpmDependencies::class)->handle(installContext($files));
    $package = $files->get('package.json');

    expect(app(AddNpmDependencies::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('package.json'))->toBe($package);
});
