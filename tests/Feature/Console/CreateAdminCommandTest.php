<?php

use Illuminate\Support\Facades\Hash;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;

it('creates a verified admin with every registered permission', function () {
    usePermissions(FakePermission::class);

    artisan('headless:create-admin', ['--name' => 'Ada Lovelace', '--email' => 'Ada@Example.com'])
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'S3cure-password!')
        ->assertSuccessful();

    $admin = User::where('email', 'ada@example.com')->firstOrFail();
    expect($admin->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('S3cure-password!', $admin->password))->toBeTrue()
        ->and($admin->can('reports.view'))->toBeTrue()
        ->and($admin->can('reports.export'))->toBeTrue();
});

it('asks for the name and email when they are not passed', function () {
    artisan('headless:create-admin')
        ->expectsQuestion('Name', 'Ada Lovelace')
        ->expectsQuestion('Email', 'ada@example.com')
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'S3cure-password!')
        ->assertSuccessful();

    expect(User::where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    artisan('headless:create-admin', ['--name' => 'Ada', '--email' => 'taken@example.com'])->assertFailed();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('rejects a mismatched password confirmation', function () {
    artisan('headless:create-admin', ['--name' => 'Ada', '--email' => 'ada@example.com'])
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'different')
        ->assertFailed();

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('refuses to run without interaction', function () {
    artisan('headless:create-admin', ['--name' => 'Ada', '--email' => 'ada@example.com', '--no-interaction' => true])->assertFailed();

    expect(User::count())->toBe(0);
});
