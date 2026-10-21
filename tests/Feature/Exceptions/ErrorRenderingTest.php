<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Api\ApiErrorRenderer;
use Ssdev\Headless\Exceptions\InertiaErrorRenderer;

use function Pest\Laravel\get;

dataset('registration orders', [
    'as the installer writes them' => [[ApiErrorRenderer::class, InertiaErrorRenderer::class]],
    'in reverse' => [[InertiaErrorRenderer::class, ApiErrorRenderer::class]],
]);

beforeEach(fn () => config(['app.debug' => false]));

function registerRenderers(array $renderers): void
{
    $exceptions = new Exceptions(app(ExceptionHandler::class));

    foreach ($renderers as $renderer) {
        $renderer::register($exceptions);
    }
}

it('keeps the api error format next to the inertia error pages', function (array $renderers) {
    registerRenderers($renderers);

    get('/api/v1/nope')->assertNotFound()->assertExactJson(['message' => __('headless::api.errors.404')]);
})->with('registration orders');

it('keeps the inertia error pages next to the api error format', function (array $renderers) {
    registerRenderers($renderers);

    get('/missing-page')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page->component(headlessPage('Errors/Error'), false)->where('status', 404));
})->with('registration orders');
