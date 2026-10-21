<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(fn () => Notification::fake());

it('renders the forgot password page', function () {
    get(route('password.request'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/ForgotPassword'), false)
        ->where('submitUrl', route('password.email'))
        ->where('status', null));
});

it('links the login page to the forgot password page', function () {
    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('passwordRequestUrl', route('password.request')));
});

it('sends a reset link to an existing account', function () {
    $user = User::factory()->create();

    post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', __('headless::auth.passwords.link_sent'));

    Notification::assertSentTo($user, ResetPassword::class);
});

it('responds identically for unknown and throttled emails', function () {
    $user = User::factory()->create();

    $known = post(route('password.email'), ['email' => $user->email]);
    $throttled = post(route('password.email'), ['email' => $user->email]);
    $unknown = post(route('password.email'), ['email' => 'ghost@example.com']);

    foreach ([$throttled, $unknown] as $response) {
        expect($response->status())->toBe($known->status())
            ->and($response->headers->get('Location'))->toBe($known->headers->get('Location'));
        $response->assertSessionHas('status', __('headless::auth.passwords.link_sent'))->assertSessionHasNoErrors();
    }

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

it('sends the reset link only after the response is ready', function () {
    $user = User::factory()->create();
    $sentBeforeResponse = null;
    Event::listen(RequestHandled::class, function () use ($user, &$sentBeforeResponse) {
        $sentBeforeResponse = Notification::sent($user, ResetPassword::class)->count();
    });

    post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', __('headless::auth.passwords.link_sent'));

    // Sending inside the request would make known emails answer slower than unknown ones.
    expect($sentBeforeResponse)->toBe(0);
    Notification::assertSentTo($user, ResetPassword::class);
});

it('renders the reset password page with the token and email', function () {
    get(route('password.reset', ['token' => 'abc', 'email' => 'ada@example.com']))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/ResetPassword'), false)
        ->where('token', 'abc')
        ->where('email', 'ada@example.com')
        ->where('submitUrl', route('password.update')));
});

it('resets the password, rotates the remember token and ends every session', function () {
    useDatabaseSessions();
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create(['remember_token' => 'old-token']);
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $token = Password::createToken($user);

    post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('headless::auth.passwords.reset'));

    $user->refresh();
    expect(Hash::check('N3w-password!', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-token')
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    Event::assertDispatched(PasswordReset::class);
});

it('revokes every api token on reset', function () {
    $user = User::factory()->create();
    $user->createToken('stolen');

    post(route('password.update'), ['token' => Password::createToken($user), 'email' => $user->email, 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertRedirect(route('login'));

    expect($user->tokens()->count())->toBe(0);
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create();

    post(route('password.update'), ['token' => 'invalid', 'email' => $user->email, 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejects a mismatched password confirmation', function () {
    $user = User::factory()->create();

    post(route('password.update'), ['token' => Password::createToken($user), 'email' => $user->email, 'password' => 'N3w-password!', 'password_confirmation' => 'other'])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('throttles reset link requests', function () {
    config(['headless.rate_limits.password_reset' => 1]);

    post(route('password.email'), ['email' => 'a@example.com']);
    post(route('password.email'), ['email' => 'b@example.com'])->assertSessionHasErrors('email');
});

it('rejects the honeypot on the forgot password form', function () {
    $user = User::factory()->create();

    post(route('password.email'), ['email' => $user->email, 'my_name' => 'bot'])->assertStatus(422);

    Notification::assertNothingSent();
});
