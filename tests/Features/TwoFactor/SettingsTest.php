<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Features\TwoFactor\TwoFactorStatus;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function confirmedPassword(User $user): void
{
    actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
}

it('requires a confirmed password', function () {
    actingAs(User::factory()->create())->get(route('headless.two-factor.edit'))->assertRedirect(route('password.confirm'));
});

it('shows the setup only while it is pending', function () {
    confirmedPassword(User::factory()->create());

    post(route('headless.two-factor.enable'))->assertRedirect(route('headless.two-factor.edit'));

    get(route('headless.two-factor.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('TwoFactor/Settings'), false)
        ->where('status', 'pending')
        ->has('setup.secret')
        ->where('setup.qr_code', fn (string $uri) => str_starts_with($uri, 'data:image/svg+xml;base64,')));
});

it('shows recovery codes once after confirmation', function () {
    $user = enabledTwoFactorUser(confirmed: false);
    confirmedPassword($user);

    post(route('headless.two-factor.confirm'), ['code' => currentOtp($user)])->assertRedirect(route('headless.two-factor.edit'));

    get(route('headless.two-factor.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', 'enabled')->where('setup', null)->has('recoveryCodes', 8));
    get(route('headless.two-factor.edit'))->assertInertia(fn (AssertableInertia $page) => $page->where('recoveryCodes', null));
});

it('rejects a wrong code', function () {
    $user = enabledTwoFactorUser(confirmed: false);
    confirmedPassword($user);

    post(route('headless.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');

    expect(app(TwoFactorService::class)->status($user->fresh()))->toBe(TwoFactorStatus::Pending);
});

it('regenerates recovery codes', function () {
    $user = enabledTwoFactorUser();
    $old = $user->recoveryCodes()[0];
    confirmedPassword($user);

    post(route('headless.two-factor.recovery-codes'))->assertRedirect(route('headless.two-factor.edit'))->assertSessionHas(Flash::RECOVERY_CODES);

    expect(app(TwoFactorService::class)->useRecoveryCode($user->fresh(), $old))->toBeFalse();
});

it('turns two-factor authentication off', function () {
    $user = enabledTwoFactorUser();
    confirmedPassword($user);

    delete(route('headless.two-factor.disable'))->assertRedirect(route('headless.two-factor.edit'));

    expect(app(TwoFactorService::class)->status($user->fresh()))->toBe(TwoFactorStatus::Disabled);
});

it('throttles code confirmation', function () {
    confirmedPassword(enabledTwoFactorUser(confirmed: false));

    foreach (range(1, 5) as $attempt) {
        post(route('headless.two-factor.confirm'), ['code' => '000000']);
    }

    post(route('headless.two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors(['code' => __('auth.throttle', ['seconds' => 60])]);
});

it('never shares the secret or recovery codes once set up', function () {
    $user = enabledTwoFactorUser();
    $secret = decrypt($user->two_factor_secret, false);
    confirmedPassword($user);

    foreach (['headless.two-factor.edit', 'headless.security.edit'] as $route) {
        $response = get(route($route))->assertOk();

        expectNoSensitiveProps($response, $user);
        expect(json_encode($response->viewData('page')['props']))->not->toContain($secret)->not->toContain($user->recoveryCodes()[0]);
    }
});

it('adds the two-factor card and navigation item', function () {
    actingAs(User::factory()->create());

    get(route('headless.security.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('twoFactor.status', 'disabled')
        ->where('twoFactor.manageUrl', route('headless.two-factor.edit'))
        ->where('headless.navigation', fn ($sections) => collect($sections)->flatMap(fn ($section) => $section['items'])->contains('key', 'two-factor')));
});
