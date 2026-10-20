<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Ssdev\Headless\Installation\ProjectFiles;

final class PublishCommand extends Command
{
    private const OVERRIDE_DIR = 'resources/js/vendor/headless';

    protected $signature = 'headless:publish
        {path : File inside resources/js, e.g. pages/Dashboard.vue}
        {--force : Overwrite an already published file}';

    protected $description = 'Copy a single headless frontend file into your project so you can customize it';

    public function handle(ProjectFiles $files): int
    {
        $path = (string) $this->argument('path');
        $packageJsDir = (string) realpath(__DIR__.'/../../resources/js');
        $source = realpath($packageJsDir.'/'.$path);

        if ($source === false || ! is_file($source)) {
            $this->components->error("File [{$path}] does not exist in the package.");

            return self::FAILURE;
        }

        if (! str_starts_with($source, $packageJsDir.DIRECTORY_SEPARATOR)) {
            $this->components->error("Invalid path [{$path}].");

            return self::FAILURE;
        }

        $relativePath = str_replace('\\', '/', substr($source, strlen($packageJsDir) + 1));
        $target = self::OVERRIDE_DIR.'/'.$relativePath;

        if ($files->exists($target) && ! $this->option('force')) {
            $this->components->error("[{$target}] is already published. Use --force to overwrite.");

            return self::FAILURE;
        }

        $files->put($target, (string) file_get_contents($source));

        $this->components->info("Published [{$target}]. This copy no longer receives package updates.");

        return self::SUCCESS;
    }
}
