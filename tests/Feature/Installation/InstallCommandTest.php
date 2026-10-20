<?php

use Ssdev\Headless\Tests\Fixtures\Features\FakeInstallStep;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

use function Pest\Laravel\artisan;

it('installs the core without prompting when no features are available', function () {
    $files = fixtureProject();

    artisan('headless:install', ['--no-interaction' => true])->assertSuccessful();

    expect($files->exists('config/headless.php'))->toBeTrue();
});

it('fails on unknown feature keys', function () {
    fixtureProject();

    artisan('headless:install', ['--features' => 'nope', '--no-interaction' => true])
        ->expectsOutputToContain('Unknown feature [nope].')
        ->assertFailed();
});

it('asks which features to install and runs their steps', function () {
    $files = fixtureProject();
    useFeatures([new FakeModule('fake', installSteps: [FakeInstallStep::class])]);

    artisan('headless:install')
        ->expectsChoice('Which features would you like to install?', ['fake'], ['fake' => 'Fake feature'])
        ->expectsConfirmation('Run npm install and npm run build now?', 'no')
        ->assertSuccessful();

    expect($files->get('config/headless.php'))->toContain("'fake',")
        ->and($files->exists('fake-step.txt'))->toBeTrue();
});
