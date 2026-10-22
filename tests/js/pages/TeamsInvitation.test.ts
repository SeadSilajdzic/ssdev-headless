import { usePage } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import TeamsInvitation from '@headless/pages/Teams/Invitation.vue';

enableAutoUnmount(afterEach);

function mountInvitation(props: { team: { name: string } | null; email: string | null; acceptUrl: string | null }) {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};

    return mount(TeamsInvitation, { props, global: { stubs: { AppLayout: { template: '<div><slot /></div>' } } } });
}

it('offers the invited account to join', () => {
    const wrapper = mountInvitation({ team: { name: 'Research' }, email: 'ada@example.com', acceptUrl: '/team-invitations/1?signature=abc' });

    expect(wrapper.find('[data-test="accept-invitation"]').exists()).toBe(true);
    expect(wrapper.find('[data-test="invitation-other-account"]').exists()).toBe(false);
});

it('tells another account that the invitation is not theirs', () => {
    const wrapper = mountInvitation({ team: null, email: null, acceptUrl: null });

    expect(wrapper.find('[data-test="accept-invitation"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="invitation-other-account"]').exists()).toBe(true);
});
