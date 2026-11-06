import { router } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import TenantsIndex from '@headless/pages/Tenants/Index.vue';

enableAutoUnmount(afterEach);

function mountIndex(manage: boolean) {
    return mount(TenantsIndex, {
        props: {
            tenants: {
                data: [
                    {
                        id: 7,
                        name: 'Acme',
                        subdomain: 'acme',
                        url: 'https://acme.example.com',
                        status: 'suspended',
                        suspended_at: '2026-10-22T10:00:00+00:00',
                        created_at: '2026-10-21T10:00:00+00:00',
                        owner: { email: 'owner@acme.test', accepted: false },
                    },
                ],
                meta: { current_page: 1, last_page: 1, from: 1, to: 1, total: 1 },
            },
            filters: { search: null, status: 'suspended', sort: null, direction: null },
            statuses: ['active', 'suspended'],
            can: { manage },
            indexUrl: '/tenants',
            createUrl: '/tenants/create',
        },
        // The select menus render through portals, which need the page attached to a document.
        attachTo: document.body,
        global: { stubs: { AppLayout: { template: '<div><slot name="actions" /><slot /></div>' } } },
    });
}

it('offers the new tenant button only to managers', () => {
    expect(mountIndex(true).find('[data-test="new-tenant"]').exists()).toBe(true);
    expect(mountIndex(false).find('[data-test="new-tenant"]').exists()).toBe(false);
});

it('links every row to its tenant', () => {
    expect(mountIndex(true).get('[data-test="open-tenant"]').attributes('href')).toBe('/tenants/7');
});

it('labels every filter on the page', () => {
    const wrapper = mountIndex(true);
    const labels = wrapper.findAll('label');

    expect(labels.map((label) => label.text())).toEqual(['tenancy.filters.search', 'tenancy.filters.status']);
    labels.forEach((label) => {
        expect(wrapper.get(`[id="${label.attributes('for')}"]`).element.matches('input, [role="combobox"]')).toBe(true);
    });
});

it('sends the search together with the current filters', async () => {
    vi.useFakeTimers();
    const visit = vi.spyOn(router, 'get').mockImplementation(() => {});
    const wrapper = mountIndex(true);

    await wrapper.get('input[type="search"]').setValue('acme');
    vi.advanceTimersByTime(1000);

    expect(visit).toHaveBeenCalledWith('/tenants', { status: 'suspended', search: 'acme' }, expect.anything());
    vi.useRealTimers();
    visit.mockRestore();
});
