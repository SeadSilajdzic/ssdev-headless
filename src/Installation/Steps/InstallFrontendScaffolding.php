<?php

namespace Ssdev\Headless\Installation\Steps;

use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;
use Ssdev\Headless\Installation\Stub;

final class InstallFrontendScaffolding implements InstallStep
{
    private const DESCRIPTION = 'Scaffold the Vue frontend';

    private const STYLESHEET_PATH = 'resources/css/app.css';

    private const STYLESHEET_IMPORT = "@import '../../vendor/ssdev/laravel-headless/resources/css/headless.css';\n";

    private const TAILWIND_IMPORT = '/@import [\'"]tailwindcss[\'"];\R/';

    private const DEFAULT_FONT_DECLARATION = "/\R[ \t]*--font-sans: 'Instrument Sans'[^;]*;/";

    private const EMPTY_THEME = "/\R@theme \{\s*\}\R?/";

    public function handle(InstallContext $context): StepResult
    {
        return StepResult::combine(self::DESCRIPTION, [
            $this->installViteConfig($context),
            $this->installEntryPoint($context),
            $this->installTsConfig($context),
            $this->installStylesheet($context),
        ]);
    }

    private function installViteConfig(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains('vite.config.ts', 'headless(')) {
            return StepResult::skipped('vite.config.ts');
        }

        if ($files->exists('vite.config.ts') && ! $context->mayOverwrite('vite.config.ts')) {
            return StepResult::manual(
                'vite.config.ts',
                'Add the vue() and headless() plugins to your Vite config and use resources/js/app.ts as the input.',
            );
        }

        if ($files->exists('vite.config.js')) {
            if (! $context->mayOverwrite('vite.config.js')) {
                return StepResult::manual(
                    'vite.config.js',
                    'Add the vue() and headless() plugins to your Vite config and use resources/js/app.ts as the input.',
                );
            }

            $files->delete('vite.config.js');
        }

        $files->put('vite.config.ts', Stub::get('vite.config.ts'));

        return StepResult::changed('vite.config.ts');
    }

    private function installEntryPoint(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains('resources/js/app.ts', 'createHeadlessApp')) {
            return StepResult::skipped('resources/js/app.ts');
        }

        if ($files->exists('resources/js/app.ts') && ! $context->mayOverwrite('resources/js/app.ts')) {
            return StepResult::manual(
                'resources/js/app.ts',
                "Boot the app from resources/js/app.ts with: createHeadlessApp({ pages: import.meta.glob('./pages/**/*.vue') })",
            );
        }

        $files->put('resources/js/app.ts', Stub::get('app.ts'));

        if ($files->exists('resources/js/app.js') && $this->mayRemoveAppJs($context)) {
            $files->delete('resources/js/app.js');
        }

        return StepResult::changed('resources/js/app.ts');
    }

    private function mayRemoveAppJs(InstallContext $context): bool
    {
        // The old Vite config still points at app.js until it is converted.
        if (! $context->files->contains('vite.config.ts', 'headless(')) {
            return false;
        }

        return in_array(trim($context->files->get('resources/js/app.js')), ['', '//'], true)
            || $context->mayOverwrite('resources/js/app.js');
    }

    private function installTsConfig(InstallContext $context): StepResult
    {
        $files = $context->files;

        if (! $files->exists('tsconfig.json')) {
            $files->put('tsconfig.json', Stub::get('tsconfig.json'));

            return StepResult::changed('tsconfig.json');
        }

        if ($files->contains('tsconfig.json', '@headless/*')) {
            return StepResult::skipped('tsconfig.json');
        }

        return StepResult::manual(
            'tsconfig.json',
            'Add to compilerOptions.paths in tsconfig.json: "@headless/*": ["./resources/js/vendor/headless/*", "./vendor/ssdev/laravel-headless/resources/js/*"]',
        );
    }

    private function installStylesheet(InstallContext $context): StepResult
    {
        $files = $context->files;

        if ($files->contains(self::STYLESHEET_PATH, 'laravel-headless/resources/css/headless.css')) {
            return StepResult::skipped(self::STYLESHEET_PATH);
        }

        if (! $files->exists(self::STYLESHEET_PATH) || ! $files->insertAfterPattern(self::STYLESHEET_PATH, self::TAILWIND_IMPORT, self::STYLESHEET_IMPORT)) {
            return StepResult::manual(
                self::STYLESHEET_PATH,
                'Add after the Tailwind import in resources/css/app.css: '.trim(self::STYLESHEET_IMPORT),
            );
        }

        $stylesheet = (string) preg_replace(self::DEFAULT_FONT_DECLARATION, '', $files->get(self::STYLESHEET_PATH));
        $files->put(self::STYLESHEET_PATH, (string) preg_replace(self::EMPTY_THEME, '', $stylesheet));

        return StepResult::changed(self::STYLESHEET_PATH);
    }
}
