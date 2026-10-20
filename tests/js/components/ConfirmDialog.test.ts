import { flushPromises, mount } from '@vue/test-utils';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';

const confirmButton = () => [...document.querySelectorAll('button')].find((button) => button.textContent?.includes('Delete'))!;

it('emits confirm when the confirm button is pressed', async () => {
    const wrapper = mount(ConfirmDialog, {
        props: { open: true, title: 'Delete?', description: 'Sure?', confirmLabel: 'Delete' },
        attachTo: document.body,
    });
    await flushPromises();
    confirmButton().click();
    await flushPromises();
    expect(wrapper.emitted('confirm')).toHaveLength(1);
});

it('disables the confirm button while loading', async () => {
    mount(ConfirmDialog, {
        props: { open: true, title: 'Delete?', description: 'Sure?', confirmLabel: 'Delete', loading: true },
        attachTo: document.body,
    });
    await flushPromises();
    expect(confirmButton().hasAttribute('disabled')).toBe(true);
});
