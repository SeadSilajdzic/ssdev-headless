<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Permissions\PermissionRegistry;

it('offers two-factor authentication and roles', function () {
    expect(array_map(fn (FeatureModule $module) => $module->key(), app(FeatureCatalog::class)->modules()))->toBe(['two-factor', 'roles']);
});

it('keeps role permissions and routes out while the feature is disabled', function () {
    expect(app(PermissionRegistry::class)->names())->not->toContain('roles.view')
        ->and(Route::has('headless.roles.index'))->toBeFalse();
});

it('keeps two-factor authentication off while the feature is disabled', function () {
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeFalse()
        ->and(Route::has('two-factor.login'))->toBeFalse();
});
