<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureModule;

it('offers two-factor authentication', function () {
    expect(array_map(fn (FeatureModule $module) => $module->key(), app(FeatureCatalog::class)->modules()))->toBe(['two-factor']);
});

it('keeps two-factor authentication off while the feature is disabled', function () {
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeFalse()
        ->and(Route::has('two-factor.login'))->toBeFalse();
});
