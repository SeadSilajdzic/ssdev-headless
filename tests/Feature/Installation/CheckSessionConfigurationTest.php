<?php

use Ssdev\Headless\Installation\Steps\CheckSessionConfiguration;
use Ssdev\Headless\Installation\StepStatus;

beforeEach(fn () => config([
    'app.url' => 'http://localhost',
    'session.driver' => 'database',
    'session.http_only' => true,
    'session.same_site' => 'lax',
    'session.secure' => null,
]));

it('passes with secure session settings', function () {
    expect(app(CheckSessionConfiguration::class)->handle(installContext(fixtureProject()))->status)->toBe(StepStatus::Skipped);
});

it('warns about a non-database session driver', function () {
    config(['session.driver' => 'file']);

    $result = app(CheckSessionConfiguration::class)->handle(installContext(fixtureProject()));

    expect($result->status)->toBe(StepStatus::Manual)->and($result->instructions)->toContain('SESSION_DRIVER=database');
});

it('warns about cookies readable by scripts or sent cross-site', function () {
    config(['session.http_only' => false, 'session.same_site' => 'none']);

    $result = app(CheckSessionConfiguration::class)->handle(installContext(fixtureProject()));

    expect($result->instructions)->toContain('SESSION_HTTP_ONLY=true')->toContain('SESSION_SAME_SITE=lax');
});

it('warns about missing secure cookies on https apps', function () {
    config(['app.url' => 'https://example.com']);

    $result = app(CheckSessionConfiguration::class)->handle(installContext(fixtureProject()));

    expect($result->status)->toBe(StepStatus::Manual)->and($result->instructions)->toContain('SESSION_SECURE_COOKIE=true');
});
