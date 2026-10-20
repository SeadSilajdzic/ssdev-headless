import { mount } from '@vue/test-utils';
import HoneypotField from '@headless/components/auth/HoneypotField.vue';
import { setPageProps } from '../setup';

it('renders a hidden field named after the shared honeypot', () => {
    setPageProps({ headless: { honeypot: 'my_name' } });
    const wrapper = mount(HoneypotField, { props: { modelValue: '' } });
    const input = wrapper.get('input');

    expect(input.attributes('name')).toBe('my_name');
    expect(input.attributes('tabindex')).toBe('-1');
    expect(input.attributes('autocomplete')).toBe('off');
    expect(wrapper.attributes('aria-hidden')).toBe('true');
});

it('reports what a bot typed', async () => {
    const wrapper = mount(HoneypotField, { props: { modelValue: '' } });

    await wrapper.get('input').setValue('bot');

    expect(wrapper.emitted('update:modelValue')).toEqual([['bot']]);
});
