import { createInertiaApp } from '@inertiajs/vue3';
import headlessPages from 'virtual:headless/pages';
import { createApp, h, type App, type DefineComponent } from 'vue';
import { resolvePage } from '@headless/support/resolvePage';
import type { PageMap } from '@headless/types';

export interface HeadlessAppOptions {
    pages: PageMap;
    setup?: (app: App) => void;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

export function createHeadlessApp(options: HeadlessAppOptions): void {
    void createInertiaApp({
        title: (title) => (title ? `${title} · ${appName}` : appName),
        resolve: (name) => resolvePage(name, headlessPages, options.pages) as Promise<DefineComponent>,
        setup({ el, App, props, plugin }) {
            const app = createApp({ render: () => h(App, props) }).use(plugin);
            options.setup?.(app);
            app.mount(el);
        },
        progress: { color: '#14B8A6' },
    });
}
