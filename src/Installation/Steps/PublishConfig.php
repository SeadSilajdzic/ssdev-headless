<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

final class PublishConfig implements InstallStep
{
    private const DESCRIPTION = 'Publish config/headless.php';

    private const PATH = 'config/headless.php';

    private const FEATURES_PATTERN = "/'features'\s*=>\s*\[[^\]]*\]/";

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;

        if (! $files->exists(self::PATH)) {
            $files->put(self::PATH, $this->withFeatures($this->packageConfig(), $context->featureKeys()));

            return StepResult::changed(self::DESCRIPTION);
        }

        $current = config('headless.features', []);
        $features = array_values(array_unique([...$current, ...$context->featureKeys()]));

        if ($features === $current) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        $contents = $files->get(self::PATH);

        if (preg_match(self::FEATURES_PATTERN, $contents) !== 1) {
            return StepResult::manual(
                self::DESCRIPTION,
                "Add these keys to the 'features' array in config/headless.php: ".implode(', ', $features),
            );
        }

        $files->put(self::PATH, $this->withFeatures($contents, $features));

        return StepResult::changed(self::DESCRIPTION);
    }

    private function packageConfig(): string
    {
        return (string) file_get_contents(__DIR__.'/../../../config/headless.php');
    }

    /**
     * @param  list<string>  $features
     */
    private function withFeatures(string $contents, array $features): string
    {
        $block = $features === []
            ? "'features' => []"
            : "'features' => [\n".implode('', array_map(fn (string $key) => "        '{$key}',\n", $features)).'    ]';

        return (string) preg_replace(self::FEATURES_PATTERN, $block, $contents, 1);
    }
}
