<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Tests\Fixtures\Location\FakeIpLocator;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    useDatabaseSessions();
    FakeIpLocator::reset();
});

function useFakeLocator(): void
{
    config(['headless.session_locations.locator' => FakeIpLocator::class]);
}

it('adds the location of each session', function () {
    useFakeLocator();
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    actingAs($user)->get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('sessions.0.location', ['city' => 'Sarajevo', 'country' => 'Bosnia and Herzegovina', 'latitude' => 43.85, 'longitude' => 18.41]));
});

it('leaves locations out while no locator is configured', function () {
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    actingAs($user)->get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page->where('sessions.0.location', null));

    expect(FakeIpLocator::$calls)->toBe(0);
});

it('never looks up local or private addresses', function () {
    useFakeLocator();
    $user = User::factory()->create();
    foreach (['127.0.0.1', '::1', '10.0.0.5', '192.168.1.9'] as $index => $ip) {
        insertSession("private-{$index}", $user, $ip);
    }

    actingAs($user)->get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('sessions', fn ($sessions) => collect($sessions)->every(fn (array $session) => $session['location'] === null)));

    expect(FakeIpLocator::$calls)->toBe(0);
});

it('looks each address up once', function () {
    useFakeLocator();
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    actingAs($user)->get(route('headless.security.edit'))->assertOk();
    get(route('headless.security.edit'))->assertOk();

    expect(FakeIpLocator::$calls)->toBe(1);
});

it('treats a failing locator as an unknown location', function () {
    useFakeLocator();
    FakeIpLocator::$throws = true;
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    actingAs($user)->get(route('headless.security.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('sessions.0.location', null));
});

it('rejects a locator that is not an IpLocator', function () {
    config(['headless.session_locations.locator' => stdClass::class]);
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    $this->withoutExceptionHandling();

    actingAs($user)->get(route('headless.security.edit'));
})->throws(LogicException::class);

it('remembers locations in a cache that refuses objects', function () {
    // Laravel 12 applications set cache.serializable_classes to false.
    config(['cache.default' => 'array', 'cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
    app('cache')->forgetDriver('array');
    app()->forgetInstance('cache.store');
    useFakeLocator();
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');

    actingAs($user)->get(route('headless.security.edit'))->assertOk();
    get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page->where('sessions.0.location.city', 'Sarajevo'));

    expect(FakeIpLocator::$calls)->toBe(1);
});

it('asks a failing locator once per page', function () {
    useFakeLocator();
    FakeIpLocator::$throws = true;
    $user = User::factory()->create();
    insertSession('in-sarajevo', $user, '203.0.113.7');
    insertSession('in-berlin', $user, '198.51.100.4');

    actingAs($user)->get(route('headless.security.edit'))->assertOk();

    expect(FakeIpLocator::$calls)->toBe(1);
});
