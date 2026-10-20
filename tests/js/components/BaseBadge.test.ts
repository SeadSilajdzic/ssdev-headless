import { mount } from '@vue/test-utils';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';

it('emits remove from a removable badge', async () => {
    const wrapper = mount(BaseBadge, { props: { removable: true }, slots: { default: 'Active' } });
    await wrapper.get('button').trigger('click');
    expect(wrapper.emitted('remove')).toHaveLength(1);
});
