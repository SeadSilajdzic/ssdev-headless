<?php

use Ssdev\Headless\Account\Location\MaxMindLocator;

// MaxMind's own test database (maxmind/MaxMind-DB, test-data), with a handful of known addresses.
function useMaxMindTestDatabase(): void
{
    config(['headless.session_locations.maxmind_database' => __DIR__.'/../../Fixtures/Location/GeoIP2-City-Test.mmdb']);
}

it('locates an address in a MaxMind city database', function () {
    useMaxMindTestDatabase();

    $location = app(MaxMindLocator::class)->locate('81.2.69.160');

    expect($location?->city)->toBe('London')
        ->and($location?->country)->toBe('United Kingdom')
        ->and($location?->latitude)->toBeFloat()
        ->and($location?->longitude)->toBeFloat();
});

it('returns null for an address the database does not know', function () {
    useMaxMindTestDatabase();

    expect(app(MaxMindLocator::class)->locate('1.1.1.1'))->toBeNull();
});

it('fails loudly when the database file is missing', function () {
    config(['headless.session_locations.maxmind_database' => __DIR__.'/missing.mmdb']);

    app(MaxMindLocator::class)->locate('81.2.69.160');
})->throws(InvalidArgumentException::class);
