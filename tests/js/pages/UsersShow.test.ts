import { usePage } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import type { UserStatus } from '@headless/components/users/UserStatusBadge.vue';
import UsersShow from '@headless/pages/Users/Show.vue';

// Page props are reset before every test, so pages from earlier tests must not stay mounted.
enableAutoUnmount(afterEach);

function mountShow(status: UserStatus, canBlock: boolean, isSelf = false) {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};

    return mount(UsersShow, {
        props: {
            user: { id: 3, name: 'Ada Lovelace', email: 'ada@example.com', avatar_url: null, status, email_verified: true, created_at: null, blocked_at: null },
            isSelf,
            sessionsSupported: true,
            can: { update: true, block: canBlock, delete: true, invite: true, manageRoles: false },
            roles: null,
            rolesUrl: null,
            indexUrl: '/users',
            updateUrl: '/users/3',
            blockUrl: '/users/3/block',
            invitationUrl: '/users/3/invitation',
            passwordResetUrl: '/users/3/password-reset',
            sessionsUrl: '/users/3/sessions',
            deleteUrl: '/users/3',
        },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' }, ConfirmDialog: true } },
    });
}

it('offers block to managers for an active user and unblock for a blocked one', () => {
    expect(mountShow('active', true).find('[data-test="block-user"]').exists()).toBe(true);
    expect(mountShow('active', true).find('[data-test="unblock-user"]').exists()).toBe(false);
    expect(mountShow('blocked', true).find('[data-test="unblock-user"]').exists()).toBe(true);
    expect(mountShow('active', false).find('[data-test="block-user"]').exists()).toBe(false);
});

it('offers neither block nor delete on your own account', () => {
    const wrapper = mountShow('active', true, true);

    expect(wrapper.find('[data-test="block-user"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="delete-user"]').exists()).toBe(false);
});

it('offers to send the invitation again only to invited users', () => {
    expect(mountShow('invited', true).find('[data-test="resend-invitation"]').exists()).toBe(true);
    expect(mountShow('active', true).find('[data-test="resend-invitation"]').exists()).toBe(false);
});
