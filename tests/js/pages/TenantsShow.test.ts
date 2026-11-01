import { router, usePage } from '@inertiajs/vue3';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import TenantsShow from '@headless/pages/Tenants/Show.vue';

enableAutoUnmount(afterEach);

// Renders the dialog's content in place, so the test can type into it and press confirm.
const ConfirmDialogStub = {
    props: ['open', 'confirmDisabled'],
    emits: ['confirm'],
    template: '<div v-if="open"><slot /><button data-test="confirm" :disabled="confirmDisabled" @click="$emit(\'confirm\')" /></div>',
};

function mountShow(manage: boolean, status: 'active' | 'suspended' = 'active') {
    // Inertia always shares validation errors; the test page leaves them out.
    usePage().props.errors = {};

    return mount(TenantsShow, {
        props: {
            tenant: {
                id: 7,
                name: 'Acme',
                subdomain: 'acme',
                url: 'https://acme.example.com',
                status,
                suspended_at: null,
                created_at: '2026-10-21T10:00:00+00:00',
                owner: { email: 'owner@acme.test', accepted: false },
            },
            can: { manage },
            indexUrl: '/tenants',
            updateUrl: '/tenants/7',
            suspensionUrl: '/tenants/7/suspension',
            ownerInvitationUrl: '/tenants/7/owner-invitation',
            deleteUrl: '/tenants/7',
        },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' }, ConfirmDialog: ConfirmDialogStub } },
    });
}

it('asks for the subdomain before deleting', async () => {
    const remove = vi.spyOn(router, 'delete').mockImplementation(() => {});
    const wrapper = mountShow(true);

    await wrapper.get('[data-test="delete-tenant"]').trigger('click');
    expect(wrapper.get('[data-test="confirm"]').attributes('disabled')).toBeDefined();

    await wrapper.get('[data-test="delete-confirmation"]').setValue('acm');
    expect(wrapper.get('[data-test="confirm"]').attributes('disabled')).toBeDefined();

    await wrapper.get('[data-test="delete-confirmation"]').setValue('acme');
    await wrapper.get('[data-test="confirm"]').trigger('click');

    expect(remove).toHaveBeenCalledWith('/tenants/7', expect.objectContaining({ data: { subdomain: 'acme' } }));
    remove.mockRestore();
});

it('offers suspend for an active tenant and resume for a suspended one', () => {
    expect(mountShow(true).find('[data-test="suspend-tenant"]').exists()).toBe(true);
    expect(mountShow(true).find('[data-test="resume-tenant"]').exists()).toBe(false);
    expect(mountShow(true, 'suspended').find('[data-test="resume-tenant"]').exists()).toBe(true);
});

it('offers no changes to viewers', () => {
    const wrapper = mountShow(false);

    expect(wrapper.find('form').exists()).toBe(false);
    expect(wrapper.find('[data-test="suspend-tenant"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="delete-tenant"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="resend-owner-invitation"]').exists()).toBe(false);
});
