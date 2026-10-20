<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

function verificationUrl(User $user, ?string $email = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($email ?? $user->email)]);
}

it('sends unverified users to the verification notice', function () {
    actingAs(User::factory()->unverified()->create())->get(route('headless.dashboard'))->assertRedirect(route('verification.notice'));
});

it('renders the verification notice with the email', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)->get(route('verification.notice'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/VerifyEmail'), false)
        ->where('email', $user->email)
        ->where('resendUrl', route('verification.send')));
});

it('redirects verified users away from the verification notice', function () {
    actingAs(User::factory()->create())->get(route('verification.notice'))->assertRedirect(route('headless.dashboard'));
});

it('verifies the email through the signed link', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    actingAs($user)->get(verificationUrl($user))->assertRedirect(route('headless.dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('rejects a verification link for another address', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)->get(verificationUrl($user, 'other@example.com'))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a tampered verification link', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)->get(verificationUrl($user).'x')->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    actingAs($user)->post(route('verification.send'))->assertSessionHas('status', __('headless::auth.verification.link_sent'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('skips verification when it is disabled', function () {
    config(['headless.email_verification' => false]);

    actingAs(User::factory()->unverified()->create())->get(route('headless.dashboard'))->assertOk();
});
