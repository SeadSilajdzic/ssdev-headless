<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the profile page without sensitive data', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('headless.profile.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Settings/Profile'), false)
        ->where('mustVerifyEmail', true)
        ->where('avatar.maxKilobytes', 2048)
        ->where('avatar.accept', ['.jpg', '.jpeg', '.png', '.gif', '.webp'])
        ->where('updateUrl', route('headless.profile.update'))
        ->where('auth.user.email', $user->email)
        ->where('headless.navigation.1.group', 'account')
        ->where('headless.navigation.1.items.0.key', 'profile'));

    expectNoSensitiveProps($response, $user);
});

it('lets unverified users open their profile', function () {
    actingAs(User::factory()->unverified()->create())->get(route('headless.profile.edit'))->assertOk();
});

it('updates the name', function () {
    $user = User::factory()->create();

    actingAs($user)->put(route('headless.profile.update'), ['name' => 'Grace Hopper', 'email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(Flash::SUCCESS, __('headless::settings.profile.saved'));

    expect($user->fresh()->name)->toBe('Grace Hopper');
});

it('requires the current password to change the email', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'new@example.com'])
        ->assertSessionHasErrors('current_password');
    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->toBe('old@example.com');
});

it('rejects an email used by another account', function () {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    actingAs($user)->put(route('headless.profile.update'), ['name' => $user->name, 'email' => 'taken@example.com', 'current_password' => 'password'])
        ->assertSessionHasErrors('email');
});

it('redirects guests to login', function () {
    get(route('headless.profile.edit'))->assertRedirect(route('login'));
});

it('links the verification notice to the profile', function () {
    actingAs(User::factory()->unverified()->create())->get(route('verification.notice'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('profileUrl', route('headless.profile.edit')));
});
