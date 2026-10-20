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

it('is idempotent', function () {
    $files = fixtureProject();
    artisan('headless:install', ['--no-interaction' => true, '--force' => true])->assertSuccessful();
    $snapshot = collect(['bootstrap/app.php', 'vite.config.ts', 'resources/css/app.css', 'resources/js/app.ts', 'package.json', 'config/headless.php'])
        ->mapWithKeys(fn (string $path) => [$path => $files->get($path)]);

    artisan('headless:install', ['--no-interaction' => true])
        ->doesntExpectOutputToContain('changed')
        ->assertSuccessful();

    $snapshot->each(fn (string $contents, string $path) => expect($files->get($path))->toBe($contents));
});

it('asks which features to install and runs their steps', function () {
    $files = fixtureProject();
    useFeatures([new FakeModule('fake', installSteps: [FakeInstallStep::class])]);

    artisan('headless:install')
        ->expectsChoice('Which features would you like to install?', ['fake'], ['fake' => 'Fake feature'])
        ->expectsConfirmation('Overwrite vite.config.js?', 'yes')
        ->expectsConfirmation('Run npm install and npm run build now?', 'no')
        ->assertSuccessful();

    expect($files->get('config/headless.php'))->toContain("'fake',")
        ->and($files->exists('fake-step.txt'))->toBeTrue();
});

it('explains that no optional features are available yet', function () {
    fixtureProject();

    artisan('headless:install', ['--no-interaction' => true])
        ->expectsOutputToContain('No optional features are available in this version yet.')
        ->assertSuccessful();
});

it('does not print the notice when features are available', function () {
    fixtureProject();
    useFeatures([new FakeModule]);

    artisan('headless:install', ['--no-interaction' => true])
        ->doesntExpectOutputToContain('No optional features are available')
        ->assertSuccessful();
});
