import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import AcceptOwnerInvitation from '@headless/pages/Tenancy/AcceptOwnerInvitation.vue';

it('sends the name and the new password twice with the token', async () => {
    const post = vi.spyOn(router, 'post').mockImplementation(() => {});
    const wrapper = mount(AcceptOwnerInvitation, {
        props: { submitUrl: '/owner-invitation', token: 'abc', email: 'owner@acme.test' },
        global: { stubs: { AuthLayout: { template: '<div><slot /></div>' } } },
    });

    await wrapper.get('input[autocomplete="name"]').setValue('Ada Lovelace');
    const passwords = wrapper.findAll('input[type="password"]');
    await passwords[0].setValue('S3cure-password!');
    await passwords[1].setValue('S3cure-password!');
    await wrapper.get('form').trigger('submit');

    expect(post).toHaveBeenCalledWith(
        '/owner-invitation',
        expect.objectContaining({ token: 'abc', email: 'owner@acme.test', name: 'Ada Lovelace', password: 'S3cure-password!', password_confirmation: 'S3cure-password!' }),
        expect.anything(),
    );
    post.mockRestore();
});
