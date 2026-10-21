<?php

use Illuminate\Support\Facades\Process;
use Spatie\Permission\Models\Permission;
use Ssdev\Headless\Tests\Fixtures\Features\FakeInstallStep;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;

// The API contract step starts artisan processes in the project; the fixture has no artisan.
beforeEach(fn () => Process::fake());

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
        ->expectsConfirmation('Run database migrations now?', 'no')
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

it('runs migrations and syncs permissions with --migrate', function () {
    fixtureProject();
    usePermissions(FakePermission::class);

    artisan('headless:install', ['--migrate' => true, '--no-interaction' => true])->assertSuccessful();

    expect(Permission::count())->toBe(2);
});

it('offers to create the first admin after migrating', function () {
    fixtureProject();

    artisan('headless:install')
        ->expectsConfirmation('Overwrite vite.config.js?', 'yes')
        ->expectsConfirmation('Run database migrations now?', 'yes')
        ->expectsConfirmation('Create the first admin account now?', 'yes')
        ->expectsQuestion('Name', 'Ada Lovelace')
        ->expectsQuestion('Email', 'ada@example.com')
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'S3cure-password!')
        ->expectsConfirmation('Run npm install and npm run build now?', 'no')
        ->assertSuccessful();

    expect(User::where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('skips the admin question when migrations were not run', function () {
    fixtureProject();

    artisan('headless:install')
        ->expectsConfirmation('Overwrite vite.config.js?', 'yes')
        ->expectsConfirmation('Run database migrations now?', 'no')
        ->doesntExpectOutputToContain('Create the first admin account now?')
        ->expectsConfirmation('Run npm install and npm run build now?', 'no')
        ->assertSuccessful();

    expect(User::count())->toBe(0);
});
