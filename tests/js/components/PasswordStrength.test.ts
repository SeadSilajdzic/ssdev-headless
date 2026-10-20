import { mount } from '@vue/test-utils';
import PasswordStrength from '@headless/components/ui/PasswordStrength.vue';

it('shows the strength label and active bars', () => {
    const wrapper = mount(PasswordStrength, { props: { password: 'Password1' } });
    expect(wrapper.findAll('[data-active="true"]')).toHaveLength(3);
    expect(wrapper.text()).toContain('Good');
});
