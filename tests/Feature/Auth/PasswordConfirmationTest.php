<?php

use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('renders the confirm password page', function () {
    actingAs(User::factory()->create())->get(route('password.confirm'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Auth/ConfirmPassword'), false)
        ->where('submitUrl', route('password.confirm.store')));
});

it('confirms the password and returns to the intended page', function () {
    actingAs(User::factory()->create())
        ->withSession(['url.intended' => url('/reports')])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(url('/reports'))
        ->assertSessionHas('auth.password_confirmed_at');
});

it('ignores an external intended url after confirming', function () {
    actingAs(User::factory()->create())
        ->withSession(['url.intended' => 'https://evil.test/steal'])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('headless.dashboard'));
});

it('rejects a wrong password', function () {
    actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'nope'])
        ->assertSessionHasErrors('password')
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('throttles password confirmation attempts', function () {
    config(['headless.rate_limits.reauthentication' => 1]);
    $user = User::factory()->create();

    actingAs($user)->post(route('password.confirm.store'), ['password' => 'nope']);
    actingAs($user)->post(route('password.confirm.store'), ['password' => 'password'])->assertStatus(429);
});
