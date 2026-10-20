import type { PageMap } from '@headless/types';

export const HEADLESS_PAGE_PREFIX = 'Headless/';

export function resolvePage(name: string, headlessPages: PageMap, appPages: PageMap): Promise<unknown> {
    const loader = name.startsWith(HEADLESS_PAGE_PREFIX)
        ? headlessPages[name.slice(HEADLESS_PAGE_PREFIX.length)]
        : appPages[`./pages/${name}.vue`];

    if (!loader) {
        return Promise.reject(new Error(`Page not found: ${name}`));
    }

    return loader();
}
