<?php

use Ssdev\Headless\Support\PackageConfig;

$defaults = [
    'features' => [],
    'api' => ['enabled' => true, 'prefix' => 'api/v1'],
    'rate_limits' => ['login' => 5, 'api' => 60, 'two_factor' => 5],
    'avatars' => ['disk' => 'public', 'mimes' => ['jpg', 'png', 'webp']],
];

it('fills nested keys a published config does not have yet', function () use ($defaults) {
    $merged = PackageConfig::merge($defaults, ['rate_limits' => ['login' => 3]]);

    expect($merged['rate_limits'])->toBe(['login' => 3, 'api' => 60, 'two_factor' => 5])
        ->and($merged['api'])->toBe(['enabled' => true, 'prefix' => 'api/v1']);
});

it('keeps lists from the published config as they are', function () use ($defaults) {
    $merged = PackageConfig::merge($defaults, ['features' => ['two-factor'], 'avatars' => ['mimes' => ['png']]]);

    expect($merged['features'])->toBe(['two-factor'])
        ->and($merged['avatars'])->toBe(['disk' => 'public', 'mimes' => ['png']]);
});

it('keeps values the application set to null', function () {
    expect(PackageConfig::merge(['api' => ['token_expiration' => 60]], ['api' => ['token_expiration' => null]])['api'])
        ->toBe(['token_expiration' => null]);
});
