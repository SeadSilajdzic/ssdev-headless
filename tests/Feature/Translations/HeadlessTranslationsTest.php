<?php

use Ssdev\Headless\Translations\HeadlessTranslations;

it('returns every package translation group for the locale', function () {
    expect(app(HeadlessTranslations::class)->forLocale('en'))->toHaveKeys(['common', 'navigation', 'errors']);
});

it('falls back to english for keys missing in the requested locale', function () {
    app('translator')->addLines(['common.actions.save' => 'Save-XX'], 'xx', 'headless');

    $translations = app(HeadlessTranslations::class)->forLocale('xx');

    expect($translations['common']['actions']['save'])->toBe('Save-XX')
        ->and($translations['common']['actions']['cancel'])->toBe('Cancel');
});
