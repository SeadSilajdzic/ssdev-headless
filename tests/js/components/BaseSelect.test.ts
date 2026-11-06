import { enableAutoUnmount, mount } from '@vue/test-utils';
import BaseSelect from '@headless/components/ui/BaseSelect.vue';

enableAutoUnmount(afterEach);

it('passes id and aria attributes to the trigger', () => {
    const wrapper = mount(BaseSelect, {
        props: { options: [{ value: 'all', label: 'All' }] },
        attrs: { id: 'status', 'aria-label': 'Status', 'aria-describedby': 'status-error' },
    });
    const trigger = wrapper.get('[role="combobox"]');

    expect(trigger.attributes('id')).toBe('status');
    expect(trigger.attributes('aria-label')).toBe('Status');
    expect(trigger.attributes('aria-describedby')).toBe('status-error');
});
