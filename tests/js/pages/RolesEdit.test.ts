import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import RolesEdit from '@headless/pages/Roles/Edit.vue';

const role = { id: 3, name: 'Exporters', permissions: ['reports.export'], users_count: 0, permissions_count: 1 };

it('explains why a role is read only instead of offering the form', () => {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};
    const wrapper = mount(RolesEdit, {
        props: {
            role,
            permissions: [],
            can: { manage: false },
            readOnlyReason: 'This role has permissions you do not have.',
            indexUrl: '/roles',
            saveUrl: '/roles/3',
            deleteUrl: '/roles/3',
            members: { data: [], meta: { current_page: 1, last_page: 1, from: null, to: null, total: 0 } },
            membersUrl: '/roles/3/members',
        },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' }, ConfirmDialog: true } },
    });

    expect(wrapper.text()).toContain('This role has permissions you do not have.');
    expect(wrapper.find('form[novalidate] input').exists()).toBe(false);
    expect(wrapper.text()).toContain('reports.export');
});
