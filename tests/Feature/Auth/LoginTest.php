<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('renders the login page', function () {
    get(route('login'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/Login'), false)
        ->where('loginUrl', route('login.store'))
        ->where('status', null)
        ->where('headless.honeypot', 'my_name'));
});

it('logs in and regenerates the session', function () {
    $user = User::factory()->create();
    $before = session()->getId();

    post(route('login.store'), ['email' => strtoupper($user->email), 'password' => 'password'])
        ->assertRedirect(route('headless.dashboard'));

    assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($before);
});

it('returns the same error for an unknown email and a wrong password', function () {
    $user = User::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'nope'])->assertSessionHasErrors(['email' => __('auth.failed')]);
    post(route('login.store'), ['email' => 'ghost@example.com', 'password' => 'nope'])->assertSessionHasErrors(['email' => __('auth.failed')]);

    assertGuest();
});

it('throttles login attempts per email and ip', function () {
    config(['headless.rate_limits.login' => 2]);
    Event::fake([Lockout::class]);
    $user = User::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'nope']);
    post(route('login.store'), ['email' => $user->email, 'password' => 'nope']);
    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    assertGuest();
    Event::assertDispatched(Lockout::class);
});

it('throttles attempts across many emails from one ip', function () {
    config(['headless.rate_limits.login_per_ip' => 3]);
    $user = User::factory()->create();

    foreach (['a@example.com', 'b@example.com', 'c@example.com'] as $email) {
        post(route('login.store'), ['email' => $email, 'password' => 'nope']);
    }

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    assertGuest();
});

it('rejects submissions that fill the honeypot', function () {
    $user = User::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'my_name' => 'bot'])->assertStatus(422);

    assertGuest();
});

it('ignores an external intended url after login', function () {
    $user = User::factory()->create();

    withSession(['url.intended' => 'https://evil.test/steal'])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('headless.dashboard'));
});

it('ignores a protocol-relative intended url after login', function () {
    $user = User::factory()->create();

    withSession(['url.intended' => '//evil.test/steal'])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('headless.dashboard'));
});

it('keeps an internal intended url after login', function () {
    $user = User::factory()->create();

    withSession(['url.intended' => url('/reports')])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/reports'));
});

it('logs out and invalidates the session', function () {
    actingAs(User::factory()->create())->post(route('logout'))->assertRedirect(route('login'));

    assertGuest();
});

it('redirects signed in users away from the login page', function () {
    actingAs(User::factory()->create())->get(route('login'))->assertRedirect(route('headless.dashboard'));
});
