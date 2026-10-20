import { flushPromises, mount } from '@vue/test-utils';
import BaseModal from '@headless/components/ui/BaseModal.vue';

it('closes on escape', async () => {
    const wrapper = mount(BaseModal, { props: { open: true, title: 'Edit' }, attachTo: document.body });
    await flushPromises();
    document.activeElement?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    await flushPromises();
    expect(wrapper.emitted('update:open')?.[0]).toEqual([false]);
});
