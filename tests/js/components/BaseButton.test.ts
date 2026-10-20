import { mount } from '@vue/test-utils';
import BaseButton from '@headless/components/ui/BaseButton.vue';

it('renders a disabled busy button while loading', () => {
    const button = mount(BaseButton, { props: { loading: true }, slots: { default: 'Save' } }).get('button');
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.attributes('aria-busy')).toBe('true');
});

it('renders an inertia link when href is given', () => {
    const wrapper = mount(BaseButton, { props: { href: '/profile' }, slots: { default: 'Profile' } });
    expect(wrapper.findComponent({ name: 'Link' }).props('href')).toBe('/profile');
});
