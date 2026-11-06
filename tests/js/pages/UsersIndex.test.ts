import { router } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import UsersIndex from '@headless/pages/Users/Index.vue';

enableAutoUnmount(afterEach);

function mountIndex(can: { create: boolean; update: boolean }, roles: { id: number; name: string }[] = []) {
    return mount(UsersIndex, {
        props: {
            users: {
                data: [{ id: 3, name: 'Ada Lovelace', email: 'ada@example.com', avatar_url: null, status: 'active', email_verified: true, created_at: '2026-10-21T10:00:00+00:00', blocked_at: null }],
                meta: { current_page: 1, last_page: 1, from: 1, to: 1, total: 1 },
            },
            filters: { search: null, status: null, verified: null, role: null, registered_from: null, registered_to: null, sort: null, direction: null },
            statuses: ['active', 'invited', 'blocked'],
            roles,
            can,
            indexUrl: '/users',
            createUrl: '/users/create',
        },
        // The select menus render through portals, which need the page attached to a document.
        attachTo: document.body,
        global: { stubs: { AppLayout: { template: '<div><slot name="actions" /><slot /></div>' } } },
    });
}

it('offers an edit link in every row and the invite button to creators', () => {
    const wrapper = mountIndex({ create: true, update: true });

    expect(wrapper.get('[data-test="open-user"]').attributes('href')).toBe('/users/3');
    expect(wrapper.get('[data-test="open-user"]').text().toLowerCase()).toContain('edit');
    expect(wrapper.find('[data-test="invite-user"]').exists()).toBe(true);
});

it('offers a view link and no invite button to viewers', () => {
    const wrapper = mountIndex({ create: false, update: false });

    expect(wrapper.get('[data-test="open-user"]').text().toLowerCase()).toContain('view');
    expect(wrapper.find('[data-test="invite-user"]').exists()).toBe(false);
});

it('labels every filter on the page', () => {
    const wrapper = mountIndex({ create: true, update: true }, [{ id: 1, name: 'Editor' }]);
    const labels = wrapper.findAll('label');

    expect(labels.map((label) => label.text())).toEqual(['search', 'status', 'verified', 'role', 'registered_from', 'registered_to'].map((filter) => `users.filters.${filter}`));
    labels.forEach((label) => {
        expect(wrapper.get(`[id="${label.attributes('for')}"]`).element.matches('input, [role="combobox"]')).toBe(true);
    });
});

it('drops a pending search when the page is left', async () => {
    vi.useFakeTimers();
    const visit = vi.spyOn(router, 'get').mockImplementation(() => {});
    const wrapper = mountIndex({ create: true, update: true });

    await wrapper.get('input[type="search"]').setValue('ada');
    wrapper.unmount();
    vi.advanceTimersByTime(1000);

    expect(visit).not.toHaveBeenCalled();
    vi.useRealTimers();
    visit.mockRestore();
});
