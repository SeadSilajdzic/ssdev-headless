<?php

use Illuminate\Support\Composer;
use Ssdev\Headless\Installation\Steps\RequireComposerPackages;
use Ssdev\Headless\Installation\StepStatus;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

it('requires the composer packages of the selected features', function () {
    $composer = $this->mock(Composer::class);
    $composer->shouldReceive('setWorkingPath')->andReturnSelf();
    $composer->shouldReceive('requirePackages')->once()->with(['vendor/fake-package:^1.0'])->andReturn(true);

    $result = app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule('fake', ['vendor/fake-package' => '^1.0'])]));

    expect($result->status)->toBe(StepStatus::Changed);
});

it('runs composer in the project, whichever directory the command started from', function () {
    $files = fixtureProject();
    $composer = $this->mock(Composer::class);
    $composer->shouldReceive('setWorkingPath')->once()->ordered()->with($files->path(''))->andReturnSelf();
    $composer->shouldReceive('requirePackages')->once()->ordered()->andReturn(true);

    $result = app(RequireComposerPackages::class)->handle(installContext($files, [new FakeModule('fake', ['vendor/fake-package' => '^1.0'])]));

    expect($result->status)->toBe(StepStatus::Changed);
});

it('skips when no selected feature needs composer packages', function () {
    $this->mock(Composer::class)->shouldNotReceive('requirePackages');

    expect(app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule]))->status)->toBe(StepStatus::Skipped);
});

it('returns manual instructions when composer fails', function () {
    $composer = $this->mock(Composer::class);
    $composer->shouldReceive('setWorkingPath')->andReturnSelf();
    $composer->shouldReceive('requirePackages')->andReturn(false);

    $result = app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule('fake', ['vendor/fake-package' => '^1.0'])]));

    expect($result->status)->toBe(StepStatus::Manual)->and($result->instructions)->toContain('composer require vendor/fake-package:^1.0');
});
