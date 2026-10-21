<?php

namespace Ssdev\Headless\Installation;

use Composer\InstalledVersions;
use Illuminate\Support\Carbon;

final class MigrationPublisher
{
    private const DIRECTORY = 'database/migrations';

    private const PERMISSION_MIGRATION = 'create_permission_tables';

    // Same name Laravel's install:api uses, so an app that already ran it is not given a second copy.
    private const TOKEN_MIGRATION = 'create_personal_access_tokens_table';

    /**
     * Copies every migration the project does not have yet, matched by name regardless of its timestamp.
     *
     * @return list<string> published file names
     */
    public function publish(ProjectFiles $files): array
    {
        $now = Carbon::now();
        $published = [];

        foreach ($this->sources() as $name => $source) {
            if ($files->glob(self::DIRECTORY."/*_{$name}.php") !== []) {
                continue;
            }

            // One second apart, so the migrator keeps the order of the list.
            $file = $now->copy()->addSeconds(count($published))->format('Y_m_d_His')."_{$name}.php";
            $files->put(self::DIRECTORY.'/'.$file, (string) file_get_contents($source));
            $published[] = $file;
        }

        return $published;
    }

    /**
     * @return array<string, string> migration name => source path
     */
    private function sources(): array
    {
        $sources = [
            self::PERMISSION_MIGRATION => InstalledVersions::getInstallPath('spatie/laravel-permission').'/database/migrations/create_permission_tables.php.stub',
            self::TOKEN_MIGRATION => InstalledVersions::getInstallPath('laravel/sanctum').'/database/migrations/2019_12_14_000001_'.self::TOKEN_MIGRATION.'.php',
        ];

        $packageMigrations = glob(__DIR__.'/../../database/migrations/*.php') ?: [];
        sort($packageMigrations);

        foreach ($packageMigrations as $path) {
            $sources[basename($path, '.php')] = $path;
        }

        return $sources;
    }
}
