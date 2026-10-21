import { mount } from '@vue/test-utils';
import RoleMembers from '@headless/components/roles/RoleMembers.vue';

const members = {
    data: [
        { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', avatar_url: null },
        { id: 2, name: 'Grace Hopper', email: 'grace@example.com', avatar_url: null },
    ],
    meta: { current_page: 1, last_page: 1, from: 1, to: 2, total: 2 },
};

function mountMembers(canManage: boolean) {
    return mount(RoleMembers, { props: { members, membersUrl: '/roles/3/members', pageUrl: (page: number) => `/roles/3?members_page=${page}`, canManage } });
}

it('lists members with a remove button only for managers', () => {
    expect(mountMembers(true).findAll('[data-test="remove-member"]')).toHaveLength(2);
    expect(mountMembers(false).findAll('[data-test="remove-member"]')).toHaveLength(0);
    expect(mountMembers(false).text()).toContain('grace@example.com');
});

it('offers the add form to managers only', () => {
    expect(mountMembers(true).find('[data-test="add-member"]').exists()).toBe(true);
    expect(mountMembers(false).find('[data-test="add-member"]').exists()).toBe(false);
});
