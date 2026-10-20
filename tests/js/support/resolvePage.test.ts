import { resolvePage } from '@headless/support/resolvePage';

it('resolves headless pages from the package map', async () => {
    const page = { name: 'Dashboard' };
    await expect(resolvePage('Headless/Dashboard', { Dashboard: async () => page }, {})).resolves.toBe(page);
});

it('resolves application pages from the glob map', async () => {
    const page = { name: 'Users' };
    await expect(resolvePage('Users/Index', {}, { './pages/Users/Index.vue': async () => page })).resolves.toBe(page);
});

it('rejects unknown pages', async () => {
    await expect(resolvePage('Nope', {}, {})).rejects.toThrow('Page not found: Nope');
});
