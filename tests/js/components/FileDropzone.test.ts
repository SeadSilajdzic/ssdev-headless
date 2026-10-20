import { mount } from '@vue/test-utils';
import FileDropzone from '@headless/components/ui/FileDropzone.vue';

it('rejects files larger than the limit', async () => {
    const wrapper = mount(FileDropzone, { props: { modelValue: null, accept: ['image/png'], maxSizeKb: 1 } });
    const file = new File(['x'.repeat(2048)], 'big.png', { type: 'image/png' });
    Object.defineProperty(wrapper.get('input').element, 'files', { value: [file] });
    await wrapper.get('input').trigger('change');
    expect(wrapper.emitted('error')?.[0]).toEqual(['The file may not be greater than 1 KB.']);
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
});

it('rejects disallowed file types', async () => {
    const wrapper = mount(FileDropzone, { props: { modelValue: null, accept: ['image/png'], maxSizeKb: 100 } });
    Object.defineProperty(wrapper.get('input').element, 'files', { value: [new File(['x'], 'a.txt', { type: 'text/plain' })] });
    await wrapper.get('input').trigger('change');
    expect(wrapper.emitted('error')?.[0]).toEqual(['This file type is not allowed.']);
});
