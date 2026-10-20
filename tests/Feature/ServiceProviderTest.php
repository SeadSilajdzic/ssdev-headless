<?php

it('merges the package config', function () {
    expect(config('headless.features'))->toBe([])
        ->and(config('headless.routes.prefix'))->toBe('')
        ->and(config('headless.routes.middleware'))->toBe(['web']);
});
