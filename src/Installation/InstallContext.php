<?php

namespace Ssdev\Headless\Installation;

use Closure;
use Ssdev\Headless\Features\FeatureModule;

final class InstallContext
{
    /**
     * @param  list<FeatureModule>  $features
     * @param  Closure(string): bool  $overwriteConfirmation
     */
    public function __construct(
        public readonly ProjectFiles $files,
        public readonly array $features,
        private Closure $overwriteConfirmation,
    ) {}

    public function mayOverwrite(string $relativePath): bool
    {
        return ($this->overwriteConfirmation)($relativePath);
    }

    /**
     * @return list<string>
     */
    public function featureKeys(): array
    {
        return array_map(fn (FeatureModule $module) => $module->key(), $this->features);
    }
}
