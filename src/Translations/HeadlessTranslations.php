<?php

namespace Ssdev\Headless\Translations;

use Illuminate\Contracts\Translation\Translator;

final class HeadlessTranslations
{
    public const SOURCE_LOCALE = 'en';

    public function __construct(
        private Translator $translator,
        private string $langPath,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function forLocale(string $locale): array
    {
        $translations = [];

        foreach ($this->groups() as $group) {
            $translations[$group] = array_replace_recursive(
                $this->group($group, self::SOURCE_LOCALE),
                $this->group($group, $locale),
            );
        }

        return $translations;
    }

    /**
     * @return list<string>
     */
    private function groups(): array
    {
        $files = glob($this->langPath.'/'.self::SOURCE_LOCALE.'/*.php') ?: [];

        return array_map(fn (string $file) => basename($file, '.php'), $files);
    }

    /**
     * @return array<string, mixed>
     */
    private function group(string $group, string $locale): array
    {
        $lines = $this->translator->get("headless::{$group}", [], $locale);

        return is_array($lines) ? $lines : [];
    }
}
