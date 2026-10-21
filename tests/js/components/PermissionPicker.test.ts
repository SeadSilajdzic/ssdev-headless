import { mount } from '@vue/test-utils';
import PermissionPicker from '@headless/components/permissions/PermissionPicker.vue';

const groups = [{ group: 'reports', label: 'Reports', abilities: [{ name: 'reports.view' }, { name: 'reports.export' }] }];

it('toggles a permission', async () => {
    const wrapper = mount(PermissionPicker, { props: { groups, modelValue: [], emptyLabel: 'Nothing to give' } });

    await wrapper.findAll('[role="checkbox"]')[0].trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['reports.view']]);
});

it('removes a permission that was picked', async () => {
    const wrapper = mount(PermissionPicker, { props: { groups, modelValue: ['reports.view', 'reports.export'], emptyLabel: 'Nothing to give' } });

    await wrapper.findAll('[role="checkbox"]')[0].trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['reports.export']]);
});

it('shows the empty label when there is nothing to pick', () => {
    const wrapper = mount(PermissionPicker, { props: { groups: [], modelValue: [], emptyLabel: 'Nothing to give' } });

    expect(wrapper.text()).toContain('Nothing to give');
    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(0);
});
