<?php

use Ssdev\Headless\Support\Isolation;
use Ssdev\Headless\Support\SharedIsolation;

it('keeps paths and keys as they are without tenancy', function () {
    $isolation = new SharedIsolation;

    expect($isolation->path('avatars'))->toBe('avatars')
        ->and($isolation->key('login|ip'))->toBe('login|ip');
});

it('is the default isolation', function () {
    expect(app(Isolation::class))->toBeInstanceOf(SharedIsolation::class);
});
