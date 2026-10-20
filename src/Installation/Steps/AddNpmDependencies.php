<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class AddNpmDependencies implements InstallStep
{
    private const DESCRIPTION = 'Add npm dependencies';

    private const DEV_DEPENDENCIES = [
        '@inertiajs/vue3' => '^3.9',
        'vue' => '^3.5',
        'reka-ui' => '^2.11',
        'lucide-vue-next' => '^1.0',
        '@vitejs/plugin-vue' => '^6.0',
        'typescript' => '~6.0',
        'vue-tsc' => '^3.3',
    ];

    private const SCRIPTS = [
        'typecheck' => 'vue-tsc --noEmit',
    ];

    public function handle(InstallContext $context): StepResult
    {
        if (! $context->files->exists('package.json')) {
            return StepResult::manual(self::DESCRIPTION, 'Create a package.json and add: '.implode(', ', array_keys(self::DEV_DEPENDENCIES)));
        }

        $package = $context->files->getJson('package.json');
        $updated = $package;
        $updated['devDependencies'] = ($package['devDependencies'] ?? []) + self::DEV_DEPENDENCIES;
        $updated['scripts'] = ($package['scripts'] ?? []) + self::SCRIPTS;

        if ($updated === $package) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        $context->files->putJson('package.json', $updated);

        return StepResult::changed(self::DESCRIPTION);
    }
}
