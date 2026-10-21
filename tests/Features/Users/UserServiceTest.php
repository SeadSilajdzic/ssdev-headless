<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Users\Events\UserDeleted;
use Ssdev\Headless\Features\Users\Notifications\UserInvitationNotification;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserService;
use Workbench\App\Models\User;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\post;

it('updates the name and email and asks to verify the new email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);

    app(UserService::class)->update($user, 'Ada Byron', ' New@Example.com ');

    $user->refresh();
    expect($user->name)->toBe('Ada Byron')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('keeps the verification when the email stays the same', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'ada@example.com']);

    app(UserService::class)->update($user, 'Ada Byron', 'ADA@example.com');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
});

it('sends the invitation again when a pending invitee gets a new email', function () {
    Notification::fake();
    $user = app(UserInvitationService::class)->invite(User::factory()->create(), 'Ada', 'ada@example.com');

    app(UserService::class)->update($user, 'Ada', 'ada@new.example');

    Notification::assertSentToTimes($user, UserInvitationNotification::class, 2);
    Notification::assertNotSentTo($user, VerifyEmail::class);
});

it('deletes a user softly and ends their access', function () {
    useDatabaseSessions();
    Event::fake([UserDeleted::class]);
    $actor = User::factory()->create();
    $user = User::factory()->create();
    insertSession('their-session', $user);
    $user->createToken('cli');

    app(UserService::class)->delete($actor, $user);

    assertSoftDeleted($user);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0);
    Event::assertDispatched(UserDeleted::class, fn (UserDeleted $event) => $event->userKey === $user->getKey()
        && $event->email === $user->email
        && $event->actor->is($actor));
});

it('refuses to delete yourself', function () {
    $actor = User::factory()->create();

    expect(fn () => app(UserService::class)->delete($actor, $actor))->toThrow(failsOn('user'))
        ->and($actor->fresh()->trashed())->toBeFalse();
});

it('refuses to delete the last active user manager', function () {
    $actor = permittedUser('users.delete');
    $manager = permittedUser('users.block');
    permittedUser('users.block')->forceFill(['blocked_at' => now()])->save();

    expect(fn () => app(UserService::class)->delete($actor, $manager))->toThrow(failsOn('user'))
        ->and($manager->fresh()->trashed())->toBeFalse();
});

it('deletes a user manager while another one stays active', function () {
    $actor = permittedUser('users.delete', 'users.block');
    $manager = permittedUser('users.block');

    app(UserService::class)->delete($actor, $manager);

    expect($manager->fresh()->trashed())->toBeTrue();
});

it('keeps a deleted user from signing in', function () {
    $user = User::factory()->create();
    $user->delete();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors(['email' => __('auth.failed')]);
});
