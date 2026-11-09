<?php

namespace Ssdev\Headless\Installation\Steps;

use Illuminate\Contracts\Config\Repository;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class LinkPublicStorage implements InstallStep
{
    private const DESCRIPTION = 'Link public/storage for profile photos';

    private const LINK = 'public/storage';

    private const TARGET = 'storage/app/public';

    private const LOCAL_DRIVER = 'local';

    public function __construct(private Repository $config) {}

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;

        if (! $this->avatarsUseLocalDisk() || $files->exists(self::LINK)) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        $files->link(self::TARGET, self::LINK);

        if (! $files->exists(self::LINK)) {
            return StepResult::manual(self::DESCRIPTION, 'Run: php artisan storage:link');
        }

        return StepResult::changed(self::DESCRIPTION);
    }

    private function avatarsUseLocalDisk(): bool
    {
        $disk = (string) $this->config->get('headless.avatars.disk');

        return $this->config->get("filesystems.disks.{$disk}.driver") === self::LOCAL_DRIVER;
    }
}
