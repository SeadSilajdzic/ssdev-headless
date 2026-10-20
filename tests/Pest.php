<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Tests\Support\FixtureProject;
use Ssdev\Headless\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->afterEach(fn () => FixtureProject::cleanup())
    ->in('Feature', 'Unit');

function headlessPage(string $name): string
{
    expect(file_exists(__DIR__."/../resources/js/pages/{$name}.vue"))->toBeTrue("Missing page resources/js/pages/{$name}.vue");

    return 'Headless/'.$name;
}

function fixtureProject(): ProjectFiles
{
    $files = FixtureProject::create();
    app()->instance(ProjectFiles::class, $files);

    return $files;
}

/**
 * @param  list<FeatureModule>  $features
 */
function installContext(ProjectFiles $files, array $features = [], bool $overwrite = false): InstallContext
{
    return new InstallContext($files, $features, fn (string $path) => $overwrite);
}

/**
 * @param  list<FeatureModule>  $available
 * @param  list<string>  $enabled
 */
function useFeatures(array $available, array $enabled = []): void
{
    app()->instance(FeatureRegistry::class, new FeatureRegistry($available, $enabled));
}
