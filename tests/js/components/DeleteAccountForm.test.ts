import { usePage } from '@inertiajs/vue3';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import DeleteAccountForm from '@headless/components/settings/DeleteAccountForm.vue';

enableAutoUnmount(afterEach);

it('explains why the account cannot be deleted', async () => {
    const page = usePage();
    page.props.errors = { account: 'This account owns a team with other members.' } as unknown as typeof page.props.errors;
    const wrapper = mount(DeleteAccountForm, { props: { deleteUrl: '/settings/profile' }, attachTo: document.body });

    await wrapper.get('button').trigger('click');
    await flushPromises();

    expect(document.body.textContent).toContain('This account owns a team with other members.');
});
