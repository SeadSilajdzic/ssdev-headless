<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Ssdev\Headless\Events\EmailChanged;
use Ssdev\Headless\Notifications\ConfirmEmailChangeNotification;
use Ssdev\Headless\Notifications\EmailChangedNotification;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

beforeEach(fn () => Notification::fake());

function confirmationUrl(User $user, string $email, ?string $issuedFor = null): string
{
    return URL::temporarySignedRoute('headless.email-change.confirm', now()->addHour(), [
        'user' => $user->id,
        'email' => $email,
        'current' => hash_hmac('sha256', $issuedFor ?? $user->email, config('app.key')),
    ]);
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

    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'password']);

    Notification::assertSentOnDemand(
        ConfirmEmailChangeNotification::class,
        fn (ConfirmEmailChangeNotification $notification) => ! str_contains($notification->url, 'old%40example.com')
            && ! str_contains($notification->url, sha1('old@example.com')),
    );
});

it('applies the new email from the confirmation link', function () {
    Event::fake([EmailChanged::class]);
    $user = User::factory()->create(['email' => 'old@example.com']);

    get(confirmationUrl($user, 'new@example.com'))
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

    actingAs($user)->get(confirmationUrl($user, 'new@example.com'))
        ->assertRedirect(route('headless.profile.edit'))
        ->assertSessionHas(Flash::SUCCESS, __('headless::settings.email_change.confirmed'));
});

it('rejects a link issued before a newer email change', function () {
    $user = User::factory()->create(['email' => 'second@example.com']);

    get(confirmationUrl($user, 'first@example.com', issuedFor: 'original@example.com'))
        ->assertSessionHas(Flash::STATUS, __('headless::settings.email_change.invalid'));

    expect($user->fresh()->email)->toBe('second@example.com');
    Notification::assertNothingSent();
});

it('rejects a link for an email another account took meanwhile', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    User::factory()->create(['email' => 'new@example.com']);

    get(confirmationUrl($user, 'new@example.com'));

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects a tampered link', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    get(str_replace('new%40example.com', 'evil%40example.com', confirmationUrl($user, 'new@example.com')))->assertForbidden();

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects an expired link', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $url = confirmationUrl($user, 'new@example.com');

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
