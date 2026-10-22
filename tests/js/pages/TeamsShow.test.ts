import { usePage } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import TeamsShow from '@headless/pages/Teams/Show.vue';

enableAutoUnmount(afterEach);

function mountTeam(isOwner: boolean) {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};

    return mount(TeamsShow, {
        props: {
            team: { id: 1, name: 'Research', personal: false, is_owner: isOwner, is_current: true },
            isOwner,
            members: [
                { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', avatar_url: null, is_owner: true },
                { id: 2, name: 'Grace Hopper', email: 'grace@example.com', avatar_url: null, is_owner: false },
            ],
            invitations: [],
            updateUrl: '/teams/1',
            deleteUrl: '/teams/1',
            leaveUrl: '/teams/1/membership',
            ownerUrl: '/teams/1/owner',
            invitationsUrl: '/teams/1/invitations',
            removeMemberUrls: { 2: '/teams/1/members/2' },
        },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' }, ConfirmDialog: true } },
    });
}

it('shows the owner the team controls', () => {
    const wrapper = mountTeam(true);

    expect(wrapper.find('[data-test="rename-team"]').exists()).toBe(true);
    expect(wrapper.find('[data-test="invite-member"]').exists()).toBe(true);
    expect(wrapper.find('[data-test="delete-team"]').exists()).toBe(true);
    expect(wrapper.findAll('[data-test="remove-member"]')).toHaveLength(1);
    expect(wrapper.find('[data-test="leave-team"]').exists()).toBe(false);
});

it('lets a member only leave', () => {
    const wrapper = mountTeam(false);

    expect(wrapper.find('[data-test="rename-team"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="invite-member"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="delete-team"]').exists()).toBe(false);
    expect(wrapper.findAll('[data-test="remove-member"]')).toHaveLength(0);
    expect(wrapper.find('[data-test="leave-team"]').exists()).toBe(true);
});
