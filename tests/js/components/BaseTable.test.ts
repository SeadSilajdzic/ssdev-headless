import { mount } from '@vue/test-utils';
import BaseTable from '@headless/components/ui/BaseTable.vue';

const columns = [{ key: 'name', label: 'Name', sortable: true }];

it('sorts ascending, then descending', async () => {
    const wrapper = mount(BaseTable, { props: { columns, rows: [{ id: 1, name: 'Ada' }], sort: null } });
    await wrapper.get('th button').trigger('click');
    expect(wrapper.emitted('update:sort')?.[0]).toEqual([{ key: 'name', direction: 'asc' }]);
    await wrapper.setProps({ sort: { key: 'name', direction: 'asc' } });
    await wrapper.get('th button').trigger('click');
    expect(wrapper.emitted('update:sort')?.[1]).toEqual([{ key: 'name', direction: 'desc' }]);
    expect(wrapper.get('th').attributes('aria-sort')).toBe('ascending');
});

it('renders the empty slot when there are no rows', () => {
    const wrapper = mount(BaseTable, { props: { columns, rows: [], sort: null }, slots: { empty: 'Nothing here' } });
    expect(wrapper.text()).toContain('Nothing here');
});
