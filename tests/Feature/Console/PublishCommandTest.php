<?php

use function Pest\Laravel\artisan;

it('copies a package file into the override directory', function () {
    $files = fixtureProject();

    artisan('headless:publish', ['path' => 'pages/Dashboard.vue'])->assertSuccessful();

    expect($files->get('resources/js/vendor/headless/pages/Dashboard.vue'))
        ->toBe(file_get_contents(__DIR__.'/../../../resources/js/pages/Dashboard.vue'));
});

it('refuses to overwrite a published file without force', function () {
    $files = fixtureProject();
    $files->put('resources/js/vendor/headless/pages/Dashboard.vue', 'custom');

    artisan('headless:publish', ['path' => 'pages/Dashboard.vue'])->expectsOutputToContain('already published')->assertFailed();
    expect($files->get('resources/js/vendor/headless/pages/Dashboard.vue'))->toBe('custom');

    artisan('headless:publish', ['path' => 'pages/Dashboard.vue', '--force' => true])->assertSuccessful();
    expect($files->get('resources/js/vendor/headless/pages/Dashboard.vue'))->not->toBe('custom');
});

it('rejects paths outside the package resources', function () {
    fixtureProject();

    artisan('headless:publish', ['path' => '../../composer.json'])->expectsOutputToContain('Invalid path')->assertFailed();
});

it('rejects files that do not exist in the package', function () {
    fixtureProject();

    artisan('headless:publish', ['path' => 'pages/Nope.vue'])->expectsOutputToContain('does not exist')->assertFailed();
});
