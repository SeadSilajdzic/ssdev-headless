import { mount } from '@vue/test-utils';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';

it('toggles password visibility', async () => {
    const wrapper = mount(PasswordInput, { props: { modelValue: 'secret' } });
    expect(wrapper.get('input').attributes('type')).toBe('password');
    await wrapper.get('button').trigger('click');
    expect(wrapper.get('input').attributes('type')).toBe('text');
    expect(wrapper.get('button').attributes('aria-pressed')).toBe('true');
    expect(wrapper.get('button').attributes('aria-label')).toBe('Hide password');
});
