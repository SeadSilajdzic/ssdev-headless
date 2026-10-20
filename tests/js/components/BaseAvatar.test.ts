import { mount } from '@vue/test-utils';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';

it('falls back to initials when there is no image', () => {
    expect(mount(BaseAvatar, { props: { name: 'Ada King Lovelace' } }).text()).toBe('AL');
});
