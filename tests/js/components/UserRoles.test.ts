import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import UserRoles from '@headless/components/users/UserRoles.vue';

const roles = [
    { id: 1, name: 'Editor', assigned: true, manageable: true },
    { id: 2, name: 'Exporter', assigned: true, manageable: false },
    { id: 3, name: 'Viewer', assigned: false, manageable: true },
];

it('lets managers tick only the roles they can manage and keeps the others', async () => {
    const save = vi.spyOn(router, 'put').mockImplementation(() => {});
    const wrapper = mount(UserRoles, { props: { roles, rolesUrl: '/users/3/roles', canManage: true } });

    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(2);
    expect(wrapper.text()).toContain('Exporter');

    await wrapper.get('[data-test="save-roles"]').trigger('click');

    expect(save).toHaveBeenCalledWith('/users/3/roles', { roles: [1, 2] }, expect.anything());
    save.mockRestore();
});

it('shows the assigned roles read only without roles.manage', () => {
    const wrapper = mount(UserRoles, { props: { roles, rolesUrl: '/users/3/roles', canManage: false } });

    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(0);
    expect(wrapper.text()).toContain('Editor');
    expect(wrapper.text()).toContain('Exporter');
    expect(wrapper.text()).not.toContain('Viewer');
    expect(wrapper.find('[data-test="save-roles"]').exists()).toBe(false);
});
