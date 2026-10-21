<?php

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Workbench\App\Models\User;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

function startChallenge(User $user): void
{
    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
}

it('sends users with two-factor authentication to the challenge', function () {
    startChallenge(enabledTwoFactorUser());

    assertGuest();
    get(route('two-factor.login'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('TwoFactor/Challenge'), false)
        ->where('submitUrl', route('two-factor.login.store'))
        ->where('loginUrl', route('login')));
});

it('signs in with a valid code and regenerates the session', function () {
    $user = enabledTwoFactorUser();
    startChallenge($user);
    $before = session()->getId();

    post(route('two-factor.login.store'), ['code' => currentOtp($user)])->assertRedirect(route('headless.dashboard'));

    assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($before);
});

it('rejects a code that was already used', function () {
    $user = enabledTwoFactorUser();
    $code = currentOtp($user);
    startChallenge($user);
    post(route('two-factor.login.store'), ['code' => $code])->assertRedirect(route('headless.dashboard'));
    post(route('logout'));

    startChallenge($user);
    post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHasErrors(['code' => __('headless::two_factor.errors.invalid_code')]);

    assertGuest();
});

it('signs in with a recovery code once', function () {
    $user = enabledTwoFactorUser();
    $code = $user->recoveryCodes()[0];
    startChallenge($user);
    post(route('two-factor.login.store'), ['recovery_code' => $code])->assertRedirect(route('headless.dashboard'));
    post(route('logout'));

    startChallenge($user);
    post(route('two-factor.login.store'), ['recovery_code' => $code])
        ->assertSessionHasErrors(['recovery_code' => __('headless::two_factor.errors.invalid_recovery_code')]);

    assertGuest();
});

it('rejects a wrong code', function () {
    Event::fake([TwoFactorAuthenticationFailed::class]);
    startChallenge(enabledTwoFactorUser());

    post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHasErrors(['code' => __('headless::two_factor.errors.invalid_code')]);

    assertGuest();
    Event::assertDispatched(TwoFactorAuthenticationFailed::class);
});

it('throttles code attempts', function () {
    startChallenge(enabledTwoFactorUser());

    foreach (range(1, 5) as $attempt) {
        post(route('two-factor.login.store'), ['code' => '000000']);
    }

    post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertSessionHasErrors(['code' => __('auth.throttle', ['seconds' => 60])]);
});

it('redirects to the login page without a pending challenge', function () {
    get(route('two-factor.login'))->assertRedirect(route('login'));
});

it('never signs in through an abandoned challenge', function () {
    startChallenge(enabledTwoFactorUser());

    get(route('headless.dashboard'))->assertRedirect(route('login'));

    assertGuest();
});

it('does not challenge users whose setup is still pending', function () {
    $user = enabledTwoFactorUser(confirmed: false);

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('headless.dashboard'));

    assertAuthenticatedAs($user);
});

it('keeps the intended url on the same host', function () {
    $user = enabledTwoFactorUser();
    withSession(['url.intended' => 'https://evil.test/phish']);
    startChallenge($user);

    post(route('two-factor.login.store'), ['code' => currentOtp($user)])->assertRedirect(route('headless.dashboard'));
});
