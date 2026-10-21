<?php

use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;

it('turns on fortify two-factor authentication with confirmation', function () {
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeTrue()
        ->and(Fortify::confirmsTwoFactorAuthentication())->toBeTrue()
        ->and(app(FeatureRegistry::class)->isEnabled(Feature::TwoFactor->value))->toBeTrue();
});
