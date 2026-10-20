<?php

use Ssdev\Headless\Installation\Steps\PublishConfig;
use Ssdev\Headless\Installation\StepStatus;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

it('publishes the config with the selected features', function () {
    $files = fixtureProject();

    $result = app(PublishConfig::class)->handle(installContext($files, [new FakeModule('fake')]));

    expect($result->status)->toBe(StepStatus::Changed)
        ->and($files->get('config/headless.php'))->toContain("'features' => [\n        'fake',\n    ],");
});

it('adds new features to an existing config without touching other options', function () {
    $files = fixtureProject();
    $files->put('config/headless.php', "<?php\n\nreturn [\n    'features' => [\n        'a',\n    ],\n    'routes' => ['prefix' => 'app'],\n];\n");
    config(['headless.features' => ['a']]);

    app(PublishConfig::class)->handle(installContext($files, [new FakeModule('b')]));

    expect($files->get('config/headless.php'))
        ->toContain("'features' => [\n        'a',\n        'b',\n    ],")
        ->toContain("'prefix' => 'app'");
});

it('skips when every selected feature is already listed', function () {
    $files = fixtureProject();
    $files->put('config/headless.php', "<?php\n\nreturn ['features' => ['fake']];\n");
    config(['headless.features' => ['fake']]);

    expect(app(PublishConfig::class)->handle(installContext($files, [new FakeModule('fake')]))->status)->toBe(StepStatus::Skipped);
});
