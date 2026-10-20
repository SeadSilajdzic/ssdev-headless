import { mount } from '@vue/test-utils';
import PinInput from '@headless/components/ui/PinInput.vue';

it('emits complete with the full code', async () => {
    const wrapper = mount(PinInput, { props: { modelValue: '' }, attachTo: document.body });
    const inputs = wrapper.findAll('input:not([aria-hidden="true"])');
    expect(inputs).toHaveLength(6);
    for (const [index, digit] of ['1', '2', '3', '4', '5', '6'].entries()) {
        await inputs[index].setValue(digit);
    }
    expect(wrapper.emitted('complete')?.[0]).toEqual(['123456']);
});
