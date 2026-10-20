<?php

use Ssdev\Headless\Account\UserAgent;

it('recognises common browsers and platforms', function (?string $userAgent, ?string $platform, ?string $browser) {
    $agent = new UserAgent($userAgent);

    expect($agent->platform())->toBe($platform)->and($agent->browser())->toBe($browser);
})->with([
    'chrome on macos' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36', 'macOS', 'Chrome'],
    'safari on ios' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'iOS', 'Safari'],
    'firefox on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0', 'Windows', 'Firefox'],
    'edge on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0', 'Windows', 'Edge'],
    'opera on linux' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 OPR/115.0.0.0', 'Linux', 'Opera'],
    'chrome on android' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36', 'Android', 'Chrome'],
    'unknown' => ['curl/8.0', null, null],
    'missing' => [null, null, null],
]);
