import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import RolesIndex from '@headless/pages/Roles/Index.vue';

function mountIndex(manage: boolean) {
    return mount(RolesIndex, {
        props: {
            roles: {
                data: [{ id: 3, name: 'Editor', users_count: 2, permissions_count: 1, created_at: null }],
                meta: { current_page: 1, last_page: 1, from: 1, to: 1, total: 1 },
            },
            filters: { search: null, sort: null, direction: null },
            can: { manage },
            indexUrl: '/roles',
            createUrl: '/roles/create',
        },
        global: { stubs: { AppLayout: { template: '<div><slot name="actions" /><slot /></div>' } } },
    });
}

it('offers managers an edit link in every row', () => {
    const link = mountIndex(true).get('[data-test="open-role"]');

    expect(link.attributes('href')).toBe('/roles/3');
    expect(link.text().toLowerCase()).toContain('edit');
});

it('offers viewers a view link instead', () => {
    expect(mountIndex(false).get('[data-test="open-role"]').text().toLowerCase()).toContain('view');
});

it('drops a pending search when the page is left', async () => {
    vi.useFakeTimers();
    const visit = vi.spyOn(router, 'get').mockImplementation(() => {});
    const wrapper = mountIndex(true);

    await wrapper.get('input[type="search"]').setValue('edi');
    wrapper.unmount();
    vi.advanceTimersByTime(1000);

    expect(visit).not.toHaveBeenCalled();
    vi.useRealTimers();
    visit.mockRestore();
});
