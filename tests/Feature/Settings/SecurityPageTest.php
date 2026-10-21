<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('has no two-factor card or route while the feature is disabled', function () {
    actingAs(User::factory()->create())
        ->get(route('headless.security.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('twoFactor'));

    expect(Route::has('headless.two-factor.edit'))->toBeFalse();
});
