<?php

use Ssdev\Headless\Features\FeatureBootstrapper;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Tests\Fixtures\Features\FakeFeatureServiceProvider;
use Ssdev\Headless\Tests\Fixtures\Features\FakeModule;

it('registers service providers of enabled features', function () {
    app(FeatureBootstrapper::class)->registerProviders(new FeatureRegistry([new FakeModule], ['fake']));

    expect(app()->getProvider(FakeFeatureServiceProvider::class))->not->toBeNull();
});

it('skips service providers of disabled features', function () {
    app(FeatureBootstrapper::class)->registerProviders(new FeatureRegistry([new FakeModule], []));

    expect(app()->getProvider(FakeFeatureServiceProvider::class))->toBeNull();
});
