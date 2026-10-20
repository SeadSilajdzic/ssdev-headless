// @vitest-environment node
import { collectPages, renderPagesModule, resolveOverride } from '@headless/vite/overrides.js';

const dirs = { packageDir: '/pkg/js', overrideDir: '/app/resources/js/vendor/headless' };

it('prefers a published override', () => {
    const exists = (path: string) => path === '/app/resources/js/vendor/headless/components/ui/BaseButton.vue';
    expect(resolveOverride('components/ui/BaseButton.vue', { ...dirs, exists })).toBe('/app/resources/js/vendor/headless/components/ui/BaseButton.vue');
});

it('falls back to the package file', () => {
    const exists = (path: string) => path === '/pkg/js/components/ui/BaseButton.vue';
    expect(resolveOverride('components/ui/BaseButton.vue', { ...dirs, exists })).toBe('/pkg/js/components/ui/BaseButton.vue');
});

it('resolves extensionless imports', () => {
    expect(resolveOverride('i18n', { ...dirs, exists: (path: string) => path === '/pkg/js/i18n.ts' })).toBe('/pkg/js/i18n.ts');
});

it('normalizes windows separators', () => {
    const exists = (path: string) => path === 'C:/pkg/js/app.ts';
    expect(resolveOverride('app', { packageDir: 'C:\\pkg\\js', overrideDir: 'C:\\app\\o', exists })).toBe('C:/pkg/js/app.ts');
});

it('lets overrides replace package pages and ignores unknown overrides', () => {
    const listVueFiles = (dir: string) => (dir === '/pkg/js/pages' ? ['Dashboard.vue', 'Errors/Error.vue'] : ['Dashboard.vue', 'Extra.vue']);
    expect(collectPages({ packagePagesDir: '/pkg/js/pages', overridePagesDir: '/app/o/pages', listVueFiles })).toEqual({
        Dashboard: '/app/o/pages/Dashboard.vue',
        'Errors/Error': '/pkg/js/pages/Errors/Error.vue',
    });
});

it('renders the virtual pages module', () => {
    expect(renderPagesModule({ Dashboard: '/a/Dashboard.vue' })).toBe('export default {\n  "Dashboard": () => import("/a/Dashboard.vue"),\n};\n');
});
