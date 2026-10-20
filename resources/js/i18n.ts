import { usePage } from '@inertiajs/vue3';
import type { Translations } from '@headless/types';

export type Replacements = Record<string, string | number>;

export function translate(translations: Translations, key: string, replacements: Replacements = {}): string {
    let value: string | Translations | undefined = translations;

    for (const segment of key.split('.')) {
        value = typeof value === 'object' && value !== null ? value[segment] : undefined;
    }

    if (typeof value !== 'string') {
        return key;
    }

    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce((line, name) => line.replaceAll(`:${name}`, String(replacements[name])), value);
}

export function useTrans(): (key: string, replacements?: Replacements) => string {
    const page = usePage();

    return (key, replacements) => translate(page.props.headless?.translations ?? {}, key, replacements);
}
