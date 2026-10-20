<?php

namespace Ssdev\Headless\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Ssdev\Headless\Installation\ProjectFiles;

final class FixtureProject
{
    /** @var list<string> */
    private static array $directories = [];

    public static function create(): ProjectFiles
    {
        $filesystem = new Filesystem;
        $directory = sys_get_temp_dir().'/headless-fixture-'.bin2hex(random_bytes(6));

        $filesystem->copyDirectory(__DIR__.'/../Fixtures/laravel-skeleton', $directory);
        self::$directories[] = $directory;

        return new ProjectFiles($directory, $filesystem);
    }

    public static function cleanup(): void
    {
        $filesystem = new Filesystem;

        foreach (self::$directories as $directory) {
            $filesystem->deleteDirectory($directory);
        }

        self::$directories = [];
    }
}
