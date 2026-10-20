<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the dashboard with shared props', function () {
    $user = User::factory()->create();

    actingAs($user)->get(route('headless.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component(headlessPage('Dashboard'), false)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.email_verified', true)
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
            ->where('headless.appName', config('app.name'))
            ->where('headless.features', [])
            ->where('headless.navigation.0.items.0.key', 'dashboard')
            ->where('headless.navigation.0.items.0.active', true)
            ->where('headless.translations.common.actions.save', 'Save')
            ->where('headless.logoutUrl', route('logout'))
            ->where('flash.success', null));
});

it('redirects guests to the login route', function () {
    get(route('headless.dashboard'))->assertRedirect(route('login'));
});

it('shares flash messages', function () {
    actingAs(User::factory()->create())
        ->withSession([Flash::SUCCESS => 'Saved.'])
        ->get(route('headless.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('flash.success', 'Saved.'));
});
