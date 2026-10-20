import { mount } from '@vue/test-utils';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';

it.each([BaseInput, PasswordInput])('passes id and aria attributes to the input element', (component) => {
    const wrapper = mount(component, { attrs: { id: 'email', 'aria-describedby': 'email-error' } });

    expect(wrapper.get('input').attributes('id')).toBe('email');
    expect(wrapper.get('input').attributes('aria-describedby')).toBe('email-error');
});
