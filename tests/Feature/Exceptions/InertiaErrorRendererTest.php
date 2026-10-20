<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Exceptions\InertiaErrorRenderer;
use Ssdev\Headless\Support\Flash;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['app.debug' => false]);
    InertiaErrorRenderer::register(new Exceptions(app(ExceptionHandler::class)));
});

it('renders the inertia error page for missing routes', function () {
    get('/missing-page')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component(headlessPage('Errors/Error'), false)
            ->where('status', 404)
            ->where('headless.translations.errors.404.title', __('headless::errors.404.title'))
            ->where('flash.success', null));
});

it('keeps json responses for api requests', function () {
    getJson('/missing-page')->assertNotFound()->assertJsonStructure(['message']);
});

it('leaves debug error pages untouched', function () {
    config(['app.debug' => true]);

    expect(get('/missing-page')->assertNotFound()->getContent())->not->toContain('Headless/Errors/Error');
});

it('redirects back with a flash message on expired pages', function () {
    Route::post('expire', fn () => abort(419))->middleware('web');

    from('/somewhere')->post('/expire')->assertRedirect('/somewhere')->assertSessionHas(Flash::ERROR);
});

it('renders server errors without exposing the exception message', function () {
    Route::get('boom', fn () => throw new RuntimeException('secret-db-password'))->middleware('web');

    get('/boom')
        ->assertStatus(500)
        ->assertDontSee('secret-db-password')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('status', 500));
});
