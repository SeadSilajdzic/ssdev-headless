<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Account\EmailChangeService;
use Ssdev\Headless\Events\EmailChanged;
use Ssdev\Headless\Notifications\ConfirmEmailChangeNotification;
use Ssdev\Headless\Notifications\EmailChangedNotification;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

beforeEach(fn () => Notification::fake());

/**
 * Requests the change through the service and returns the link that was mailed to the new address.
 */
function issueConfirmationUrl(User $user, string $email): string
{
    app(EmailChangeService::class)->request($user, $email);

    $url = null;
    Notification::assertSentOnDemand(
        ConfirmEmailChangeNotification::class,
        function (ConfirmEmailChangeNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$url) {
            $url = $notification->url;

            return $notifiable->routes['mail'] === $email;
        },
    );

    return $url;
}

it('sends a confirmation link to the new address and keeps the old one active', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'password'])
        ->assertSessionHas(Flash::SUCCESS, __('headless::settings.profile.email_confirmation_sent'));

    expect($user->fresh()->email)->toBe('old@example.com');
    Notification::assertSentOnDemand(
        ConfirmEmailChangeNotification::class,
        fn (ConfirmEmailChangeNotification $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'new@example.com',
    );
});

it('does not put the current email in the confirmation link', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $url = issueConfirmationUrl($user, 'new@example.com');

    expect($url)->not->toContain('old%40example.com')->not->toContain(sha1('old@example.com'));
});

it('applies the new email from the confirmation link', function () {
    Event::fake([EmailChanged::class]);
    $user = User::factory()->create(['email' => 'old@example.com']);

    get(issueConfirmationUrl($user, 'new@example.com'))
        ->assertRedirect(route('login'))
        ->assertSessionHas(Flash::STATUS, __('headless::settings.email_change.confirmed'));

    expect($user->fresh()->email)->toBe('new@example.com')
        ->and($user->fresh()->email_verified_at)->not->toBeNull();
    Notification::assertSentOnDemand(
        EmailChangedNotification::class,
        fn (EmailChangedNotification $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'old@example.com',
    );
    Event::assertDispatched(EmailChanged::class, fn (EmailChanged $event) => $event->oldEmail === 'old@example.com' && $event->newEmail === 'new@example.com');
});

it('returns signed in users to their profile after confirming', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = issueConfirmationUrl($user, 'new@example.com');

    actingAs($user)->get($url)
        ->assertRedirect(route('headless.profile.edit'))
        ->assertSessionHas(Flash::SUCCESS, __('headless::settings.email_change.confirmed'));
});

it('rejects a link issued before a newer email change', function () {
    $user = User::factory()->create(['email' => 'first@example.com']);
    $url = issueConfirmationUrl($user, 'typo@example.com');
    $user->forceFill(['email' => 'second@example.com', 'email_verified_at' => now()])->save();

    get($url)->assertSessionHas(Flash::STATUS, __('headless::settings.email_change.invalid'));

    expect($user->fresh()->email)->toBe('second@example.com');
    Notification::assertSentOnDemandTimes(EmailChangedNotification::class, 0);
});

it('rejects a link once the email changed away and back again', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = issueConfirmationUrl($user, 'typo@example.com');

    travel(1)->minute();
    $user->forceFill(['email' => 'other@example.com', 'email_verified_at' => now()])->save();
    travel(1)->minute();
    $user->forceFill(['email' => 'old@example.com', 'email_verified_at' => now()])->save();

    get($url)->assertSessionHas(Flash::STATUS, __('headless::settings.email_change.invalid'));

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects a link for an email another account took meanwhile', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = issueConfirmationUrl($user, 'new@example.com');
    User::factory()->create(['email' => 'new@example.com']);

    get($url);

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects a tampered link', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = issueConfirmationUrl($user, 'new@example.com');

    get(str_replace('new%40example.com', 'evil%40example.com', $url))->assertForbidden();

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects an expired link', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = issueConfirmationUrl($user, 'new@example.com');

    travel(2)->hours();

    get($url)->assertForbidden();
    expect($user->fresh()->email)->toBe('old@example.com');
});

it('changes an unverified email directly and sends a new verification email', function () {
    $user = User::factory()->unverified()->create(['email' => 'typo@example.com']);

    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'right@example.com', 'current_password' => 'password'])
        ->assertSessionHas(Flash::SUCCESS, __('headless::settings.profile.saved'));

    expect($user->fresh()->email)->toBe('right@example.com')
        ->and($user->fresh()->email_verified_at)->toBeNull();
    Notification::assertSentTo($user->fresh(), VerifyEmail::class);
});
