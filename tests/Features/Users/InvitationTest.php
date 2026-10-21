<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Users\Events\UserInvited;
use Ssdev\Headless\Features\Users\Notifications\UserInvitationNotification;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Features\Users\UserStatus;
use Workbench\App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

const INVITEE_PASSWORD = 'S3cure-password!';

/**
 * The token of the last invitation the user was sent.
 */
function invitationToken(User $user): string
{
    $token = '';

    Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    return $token;
}

function acceptInvitation(string $token, string $email, string $password = INVITEE_PASSWORD): TestResponse
{
    return post(route('headless.invitation.store'), ['token' => $token, 'email' => $email, 'password' => $password, 'password_confirmation' => $password]);
}

function invite(string $email = 'ada@example.com'): User
{
    return app(UserInvitationService::class)->invite(User::factory()->create(), 'Ada Lovelace', $email);
}

beforeEach(fn () => Notification::fake());

it('invites a user who sets a password once', function () {
    Event::fake([UserInvited::class]);
    $admin = User::factory()->create();

    $user = app(UserInvitationService::class)->invite($admin, 'Ada Lovelace', ' Ada@Example.com ');

    expect($user->email)->toBe('ada@example.com')
        ->and(UserStatus::of($user))->toBe(UserStatus::Invited)
        ->and($user->hasVerifiedEmail())->toBeFalse();
    Event::assertDispatched(UserInvited::class, fn (UserInvited $event) => $event->user->is($user) && $event->actor->is($admin));

    $token = invitationToken($user);
    acceptInvitation($token, 'ada@example.com')->assertRedirect(route('login'))->assertSessionHas('status');

    $user->refresh();
    expect(Hash::check(INVITEE_PASSWORD, $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(UserStatus::of($user))->toBe(UserStatus::Active);

    acceptInvitation($token, 'ada@example.com', 'An0ther-password!')->assertSessionHasErrors('email');
});

it('mails a link to the invitation page that says when it expires', function () {
    $user = invite();

    $mail = (new UserInvitationNotification(invitationToken($user)))->toMail($user);

    expect($mail->actionUrl)->toBe(route('headless.invitation.show', ['token' => invitationToken($user), 'email' => 'ada@example.com']))
        ->and(implode(' ', $mail->outroLines))->toContain('1 week');
});

it('rejects an expired invitation', function () {
    $user = invite();
    $token = invitationToken($user);

    travel(10081)->minutes();

    acceptInvitation($token, 'ada@example.com')->assertSessionHasErrors(['email' => __('headless::users.errors.invitation_invalid')]);
});

it('accepts an invitation only for its own email', function () {
    $token = invitationToken(invite());
    User::factory()->create(['email' => 'grace@example.com']);

    acceptInvitation($token, 'grace@example.com')->assertSessionHasErrors('email');
    acceptInvitation($token, 'nobody@example.com')->assertSessionHasErrors('email');
});

it('replaces the old link when the invitation is sent again', function () {
    $user = invite();
    $first = invitationToken($user);

    travel(61)->seconds();
    app(UserInvitationService::class)->resend($user);
    $second = invitationToken($user);

    expect($second)->not->toBe($first);
    acceptInvitation($first, 'ada@example.com')->assertSessionHasErrors('email');
    acceptInvitation($second, 'ada@example.com')->assertRedirect(route('login'));
});

it('refuses to resend an invitation to an active user', function () {
    expect(fn () => app(UserInvitationService::class)->resend(User::factory()->create()))->toThrow(failsOn('user'));
});

it('completes a pending invitation through a password reset', function () {
    $user = invite();
    $token = Password::broker('users')->createToken($user);

    post(route('password.update'), ['token' => $token, 'email' => 'ada@example.com', 'password' => INVITEE_PASSWORD, 'password_confirmation' => INVITEE_PASSWORD])
        ->assertSessionHasNoErrors();

    expect(UserStatus::of($user->refresh()))->toBe(UserStatus::Active)
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('shows the invitation page with the token and email', function () {
    get(route('headless.invitation.show', ['token' => 'abc', 'email' => 'ada@example.com']))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Users/AcceptInvitation'), false)
        ->where('token', 'abc')
        ->where('email', 'ada@example.com')
        ->where('submitUrl', route('headless.invitation.store')));
});

it('counts a deleted user as taking the email', function () {
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    expect(app(UserRepository::class)->emailTaken('gone@example.com'))->toBeTrue();
});
