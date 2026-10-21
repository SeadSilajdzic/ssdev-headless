<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Ssdev\Headless\Api\ApiErrorRenderer;
use Workbench\App\Models\User;

use function Pest\Laravel\call;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    config(['app.debug' => false]);
    ApiErrorRenderer::register(new Exceptions(app(ExceptionHandler::class)));
});

it('answers unknown api routes with json even without an accept header', function () {
    get('/api/v1/nope')->assertNotFound()->assertExactJson(['message' => __('headless::api.errors.404')]);
});

it('hides the model class of a missing record', function () {
    apiTestRoute('get', 'users/{user}', fn (User $user) => $user->getKey());

    get('/api/v1/users/999')->assertNotFound()->assertExactJson(['message' => __('headless::api.errors.404')]);
});

it('hides server errors', function () {
    apiTestRoute('get', 'boom', fn () => throw new RuntimeException('SQLSTATE secret-table'));

    get('/api/v1/boom')->assertStatus(500)->assertExactJson(['message' => __('headless::api.errors.500')]);
});

it('keeps validation errors with their fields', function () {
    apiTestRoute('post', 'validate', fn (Request $request) => $request->validate(['name' => 'required']));

    post('/api/v1/validate')->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['name']]);
});

it('requires authentication on protected routes', function () {
    apiTestRoute('get', 'private', fn () => 'ok', ['auth:sanctum']);

    get('/api/v1/private')->assertUnauthorized()->assertExactJson(['message' => __('headless::api.errors.401')]);
});

it('throttles the api per client', function () {
    config(['headless.rate_limits.api' => 1]);
    apiTestRoute('get', 'ping', fn () => 'pong');

    get('/api/v1/ping')->assertOk();
    get('/api/v1/ping')->assertStatus(429)->assertHeader('Retry-After')->assertExactJson(['message' => __('headless::api.errors.429')]);
});

it('answers every api route with 404 when the api is disabled', function () {
    config(['headless.api.enabled' => false]);
    apiTestRoute('get', 'ping', fn () => 'pong');

    get('/api/v1/ping')->assertNotFound()->assertExactJson(['message' => __('headless::api.errors.404')]);
});

it('leaves errors untouched in debug mode', function () {
    config(['app.debug' => true]);
    apiTestRoute('get', 'boom', fn () => throw new RuntimeException('visible while debugging'));

    expect(get('/api/v1/boom')->assertStatus(500)->json('message'))->toBe('visible while debugging');
});

it('answers oversized requests with json even without an accept header', function () {
    apiTestRoute('post', 'upload', fn () => 'ok');

    call('POST', '/api/v1/upload', server: ['CONTENT_LENGTH' => PHP_INT_MAX])
        ->assertStatus(413)
        ->assertExactJson(['message' => __('headless::api.errors.413')]);
});

it('answers unknown api routes with 404 for every method', function () {
    post('/api/v1/nope')->assertNotFound()->assertExactJson(['message' => __('headless::api.errors.404')]);
});
