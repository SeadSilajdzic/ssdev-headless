<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Ssdev\Headless\Features\Users\Events\UserBlocked;
use Ssdev\Headless\Features\Users\Events\UserUnblocked;
use Ssdev\Headless\Features\Users\UserBlockService;
use Ssdev\Headless\Features\Users\UserStatus;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

function blockedUser(): User
{
    return User::factory()->create(['blocked_at' => now()]);
}

it('blocks a user and ends their sessions, tokens and remember cookie', function () {
    useDatabaseSessions();
    Event::fake([UserBlocked::class]);
    $actor = User::factory()->create();
    $user = User::factory()->create(['remember_token' => 'old-remember-token']);
    insertSession('their-session', $user);
    $user->createToken('cli');

    app(UserBlockService::class)->block($actor, $user);

    $user->refresh();
    expect(UserStatus::of($user))->toBe(UserStatus::Blocked)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->remember_token)->not->toBe('old-remember-token');
    Event::assertDispatched(UserBlocked::class, fn (UserBlocked $event) => $event->user->is($user) && $event->actor->is($actor));
});

it('blocks a blocked user only once', function () {
    Event::fake([UserBlocked::class]);

    app(UserBlockService::class)->block(User::factory()->create(), blockedUser());

    Event::assertNotDispatched(UserBlocked::class);
});

it('refuses to block yourself', function () {
    $actor = User::factory()->create();

    expect(fn () => app(UserBlockService::class)->block($actor, $actor))->toThrow(failsOn('user'))
        ->and(UserStatus::of($actor->refresh()))->toBe(UserStatus::Active);
});

it('unblocks a user', function () {
    Event::fake([UserUnblocked::class]);
    $actor = User::factory()->create();
    $user = blockedUser();

    app(UserBlockService::class)->unblock($actor, $user);

    expect(UserStatus::of($user->refresh()))->toBe(UserStatus::Active);
    Event::assertDispatched(UserUnblocked::class, fn (UserUnblocked $event) => $event->user->is($user) && $event->actor->is($actor));
});

it('refuses to sign in a blocked user with the right password', function () {
    $user = blockedUser();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors(['email' => __('headless::users.errors.blocked')]);

    assertGuest();
});

it('answers a wrong password of a blocked user like any other', function () {
    $user = blockedUser();

    post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors(['email' => __('auth.failed')]);
});

it('refuses an api token to a blocked user', function () {
    $user = blockedUser();

    postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('headless::users.errors.blocked')]);

    expect($user->tokens()->count())->toBe(0);
});

it('refuses a blocked user on every login, also after a two-factor challenge', function () {
    $user = blockedUser();

    expect(fn () => Auth::guard('web')->login($user))->toThrow(failsOn('email'))
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('logs out a blocked user on the next request', function () {
    actingAs(blockedUser())->get(route('headless.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('headless::users.errors.blocked')]);

    assertGuest();
});

it('rejects api requests of a blocked user and drops the token', function () {
    $user = blockedUser();
    $token = $user->createToken('cli')->plainTextToken;

    withToken($token)->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('message', __('headless::users.errors.blocked'));

    expect($user->tokens()->count())->toBe(0);
});
