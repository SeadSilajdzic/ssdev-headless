<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Workbench\App\Models\User;

use function Pest\Laravel\postJson;

beforeEach(fn () => Notification::fake());

it('registers through the api without revealing existing accounts', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $fresh = postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'new@example.com', 'password' => 'S3cure-password!']);
    $taken = postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'taken@example.com', 'password' => 'S3cure-password!']);

    $fresh->assertAccepted()->assertExactJson(['message' => __('headless::auth.registration.check_inbox')]);
    expect($taken->status())->toBe($fresh->status())
        ->and($taken->json())->toBe($fresh->json())
        ->and(User::where('email', 'new@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('hides api registration when registration is disabled', function () {
    config(['headless.registration.enabled' => false]);

    postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'new@example.com', 'password' => 'S3cure-password!'])->assertNotFound();

    expect(User::count())->toBe(0);
});

it('validates api registration', function () {
    postJson('/api/v1/auth/register', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('sends reset links through the api without revealing accounts', function () {
    $user = User::factory()->create();

    $known = postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertAccepted();
    $unknown = postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com'])->assertAccepted();

    expect($unknown->json())->toBe($known->json())
        ->and($known->json('message'))->toBe(__('headless::auth.passwords.link_sent'));
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

it('throttles api registration', function () {
    config(['headless.rate_limits.registration' => 1]);

    postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'a@example.com', 'password' => 'S3cure-password!']);
    postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'b@example.com', 'password' => 'S3cure-password!'])->assertStatus(429);

    expect(User::where('email', 'b@example.com')->exists())->toBeFalse();
});
