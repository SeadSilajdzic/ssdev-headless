import { router } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import TeamMembers from '@headless/components/teams/TeamMembers.vue';

enableAutoUnmount(afterEach);

const members = [
    { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', avatar_url: null, is_owner: true },
    { id: 2, name: 'Grace Hopper', email: 'grace@example.com', avatar_url: null, is_owner: false },
];

const roles = [
    { id: 10, name: 'Editor' },
    { id: 11, name: 'Viewer' },
];

function mountMembers(isOwner: boolean) {
    return mount(TeamMembers, {
        props: {
            members,
            isOwner,
            ownerUrl: '/teams/1/owner',
            removeMemberUrls: { 2: '/teams/1/members/2' },
            roles,
            memberRoles: { 1: [], 2: [10] },
            memberRolesUrls: isOwner ? { 1: '/teams/1/members/1/roles', 2: '/teams/1/members/2/roles' } : {},
        },
        global: { stubs: { ConfirmDialog: true } },
    });
}

it('lets the owner pick roles per member', async () => {
    const save = vi.spyOn(router, 'put').mockImplementation(() => {});
    const wrapper = mountMembers(true);

    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(4);

    await wrapper.get('[data-test="save-member-roles-2"]').trigger('click');

    expect(save).toHaveBeenCalledWith('/teams/1/members/2/roles', { roles: [10] }, expect.anything());
    save.mockRestore();
});

it('shows members their roles without letting them change them', () => {
    const wrapper = mountMembers(false);

    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(0);
    expect(wrapper.text()).toContain('Editor');
});
