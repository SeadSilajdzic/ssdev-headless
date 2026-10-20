<?php

it('inserts text after the first match of a pattern', function () {
    $files = fixtureProject();

    expect($files->insertAfterPattern('resources/css/app.css', "/@import 'tailwindcss';\R/", "/* x */\n"))->toBeTrue()
        ->and($files->get('resources/css/app.css'))->toContain("@import 'tailwindcss';\n/* x */\n");
});

it('reports a missing anchor without touching the file', function () {
    $files = fixtureProject();
    $before = $files->get('resources/css/app.css');

    expect($files->insertAfterPattern('resources/css/app.css', '/does-not-exist/', 'x'))->toBeFalse()
        ->and($files->get('resources/css/app.css'))->toBe($before);
});

it('writes pretty printed json with unescaped slashes', function () {
    $files = fixtureProject();
    $files->putJson('x.json', ['a' => 'b/c']);

    expect($files->get('x.json'))->toBe("{\n    \"a\": \"b/c\"\n}\n");
});

it('globs project paths relative to the root', function () {
    $files = fixtureProject();
    $files->put('database/migrations/2026_01_01_000000_a.php', '<?php');
    $files->put('database/migrations/2026_01_01_000001_b.php', '<?php');

    expect($files->glob('database/migrations/*_a.php'))->toBe(['database/migrations/2026_01_01_000000_a.php'])
        ->and($files->glob('missing/*.php'))->toBe([]);
});
