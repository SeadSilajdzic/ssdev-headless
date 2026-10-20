<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Notifications\AccountCreatedNotification;
use Ssdev\Headless\Notifications\RegistrationAttemptedNotification;
use Workbench\App\Models\PlainUser;
use Workbench\App\Models\User;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(fn () => Notification::fake());

/**
 * @param  array<string, mixed>  $overrides
 */
function submitRegistration(array $overrides = []): TestResponse
{
    return post(route('register.store'), [...['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'S3cure-password!'], ...$overrides]);
}

it('renders the registration page', function () {
    get(route('register'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/Register'), false)
        ->where('submitUrl', route('register.store'))
        ->where('loginUrl', route('login')));
});

it('links the login page to registration', function () {
    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('registerUrl', route('register')));
});

it('creates an unverified account without signing in', function () {
    submitRegistration()->assertRedirect(route('login'))->assertSessionHas('status', __('headless::auth.registration.check_inbox'));

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('S3cure-password!', $user->password))->toBeTrue();
    assertGuest();
});

it('sends the verification email to a new account', function () {

    submitRegistration();

    Notification::assertSentTo(User::where('email', 'ada@example.com')->firstOrFail(), VerifyEmail::class);
});

it('sends an account created email when verification is not required', function () {
    config(['auth.providers.users.model' => PlainUser::class]);

    submitRegistration();

    Notification::assertSentTo(PlainUser::where('email', 'ada@example.com')->firstOrFail(), AccountCreatedNotification::class);
});

it('responds identically when the email is already registered', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);

    $fresh = submitRegistration(['email' => 'new@example.com']);
    $taken = submitRegistration(['email' => 'taken@example.com']);

    expect($taken->status())->toBe($fresh->status())
        ->and($taken->headers->get('Location'))->toBe($fresh->headers->get('Location'))
        ->and(User::where('email', 'taken@example.com')->count())->toBe(1);
    $taken->assertSessionHas('status', __('headless::auth.registration.check_inbox'));
    Notification::assertSentTo($existing, RegistrationAttemptedNotification::class);
    Notification::assertNotSentTo($existing, VerifyEmail::class);
});

it('treats a duplicate insert race as an existing account', function () {
    User::creating(function (User $user) {
        DB::table('users')->insert(['name' => 'Racer', 'email' => $user->email, 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
    });

    submitRegistration()->assertRedirect(route('login'))->assertSessionHas('status', __('headless::auth.registration.check_inbox'));

    $racer = User::where('email', 'ada@example.com')->sole();
    expect($racer->name)->toBe('Racer');
    Notification::assertSentTo($racer, RegistrationAttemptedNotification::class);
});

it('lowercases the email', function () {
    submitRegistration(['email' => 'Ada@Example.COM']);

    expect(User::where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('validates the registration fields', function () {
    post(route('register.store'), [])->assertSessionHasErrors(['name', 'email', 'password']);
});

it('does not reveal taken emails through validation', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    submitRegistration(['email' => 'taken@example.com'])->assertSessionHasNoErrors();
});

it('hides registration when it is disabled', function () {
    config(['headless.registration.enabled' => false]);

    get(route('register'))->assertNotFound();
    submitRegistration()->assertNotFound();
    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('registerUrl', null));
    expect(User::count())->toBe(0);
});

it('throttles registration per ip', function () {
    config(['headless.rate_limits.registration' => 1]);

    submitRegistration();
    submitRegistration(['email' => 'second@example.com'])->assertSessionHasErrors('email');

    expect(User::where('email', 'second@example.com')->exists())->toBeFalse();
});

it('rejects submissions that fill the honeypot', function () {
    submitRegistration(['my_name' => 'bot'])->assertStatus(422);

    expect(User::count())->toBe(0);
});
