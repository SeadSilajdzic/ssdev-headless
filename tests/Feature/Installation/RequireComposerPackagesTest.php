<?php

use Illuminate\Support\Composer;
use Ssdev\Headless\Installation\Steps\RequireComposerPackages;
use Ssdev\Headless\Installation\StepStatus;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

it('requires the composer packages of the selected features', function () {
    $this->mock(Composer::class)->shouldReceive('requirePackages')->once()->with(['vendor/fake-package:^1.0'])->andReturn(true);

    $result = app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule('fake', ['vendor/fake-package' => '^1.0'])]));

    expect($result->status)->toBe(StepStatus::Changed);
});

it('skips when no selected feature needs composer packages', function () {
    $this->mock(Composer::class)->shouldNotReceive('requirePackages');

    expect(app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule]))->status)->toBe(StepStatus::Skipped);
});

it('returns manual instructions when composer fails', function () {
    $this->mock(Composer::class)->shouldReceive('requirePackages')->andReturn(false);

    $result = app(RequireComposerPackages::class)->handle(installContext(fixtureProject(), [new FakeModule('fake', ['vendor/fake-package' => '^1.0'])]));

    expect($result->status)->toBe(StepStatus::Manual)->and($result->instructions)->toContain('composer require vendor/fake-package:^1.0');
});
