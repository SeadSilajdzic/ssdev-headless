<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('has no two-factor card or route while the feature is disabled', function () {
    actingAs(User::factory()->create())
        ->get(route('headless.security.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('twoFactor'));

    expect(Route::has('headless.two-factor.edit'))->toBeFalse();
});

it('does not build the two-factor service while the feature is disabled', function () {
    app()->bind(TwoFactorService::class, fn () => throw new RuntimeException('The feature is off.'));

    actingAs(User::factory()->create())->get(route('headless.security.edit'))->assertOk();
});
