import { mount } from '@vue/test-utils';
import CreateApiTokenForm from '@headless/components/settings/CreateApiTokenForm.vue';

const permissions = [{ group: 'reports', label: 'Reports', abilities: [{ name: 'reports.view' }, { name: 'reports.export' }] }];

it('shows the created token with a copy button', () => {
    const wrapper = mount(CreateApiTokenForm, { props: { permissions, storeUrl: '/settings/api-tokens', createdToken: '1|abc' } });

    expect(wrapper.text()).toContain('1|abc');
    expect(wrapper.find('[data-test="copy-token"]').exists()).toBe(true);
});

it('lists one checkbox per grantable permission', () => {
    const wrapper = mount(CreateApiTokenForm, { props: { permissions, storeUrl: '/settings/api-tokens', createdToken: null } });

    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(2);
    expect(wrapper.find('[data-test="copy-token"]').exists()).toBe(false);
});
