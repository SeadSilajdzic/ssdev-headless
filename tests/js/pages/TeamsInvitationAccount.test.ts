import { router, usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import TeamsInvitationAccount from '@headless/pages/Teams/InvitationAccount.vue';

it('sends the name and the new password twice', async () => {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};
    const post = vi.spyOn(router, 'post').mockImplementation(() => {});
    const wrapper = mount(TeamsInvitationAccount, {
        props: { team: { name: 'Research' }, email: 'grace@example.com', submitUrl: '/team-invitations/4/account?signature=abc' },
        global: { stubs: { AuthLayout: { template: '<div><slot /></div>' } } },
    });

    await wrapper.get('input[autocomplete="name"]').setValue('Grace Hopper');
    const passwords = wrapper.findAll('input[type="password"]');
    await passwords[0].setValue('S3cure-password!');
    await passwords[1].setValue('S3cure-password!');
    await wrapper.get('form').trigger('submit');

    expect(post).toHaveBeenCalledWith(
        '/team-invitations/4/account?signature=abc',
        expect.objectContaining({ name: 'Grace Hopper', password: 'S3cure-password!', password_confirmation: 'S3cure-password!' }),
        expect.anything(),
    );
    post.mockRestore();
});
