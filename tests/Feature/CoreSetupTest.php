<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('migrates the permission tables and the avatar column', function () {
    expect(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('model_has_permissions'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'avatar_path'))->toBeTrue();
});

it('keeps fortify from registering its own routes', function () {
    expect(Route::has('user-profile-information.update'))->toBeFalse()
        ->and(Route::has('user-password.update'))->toBeFalse();
});

it('configures fortify through the package', function () {
    expect(config('fortify.lowercase_usernames'))->toBeTrue()
        ->and(config('fortify.username'))->toBe('email')
        ->and(config('fortify.features'))->toBe([]);
});

it('ships the core config defaults', function () {
    expect(config('headless.registration.enabled'))->toBeTrue()
        ->and(config('headless.email_verification'))->toBeTrue()
        ->and(config('headless.rate_limits.login'))->toBe(5)
        ->and(config('headless.avatars.max_kilobytes'))->toBe(2048);
});
