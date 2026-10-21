import { mount } from '@vue/test-utils';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

it('keeps the toast region out of the page grid so both columns fill the screen', () => {
    const wrapper = mount(AuthLayout, { props: { title: 'Sign in' }, slots: { default: '<p>form</p>' }, attachTo: document.body });
    const grid = document.querySelector('.min-h-screen');

    expect(document.querySelector('[role="region"]')).not.toBeNull();
    expect(grid?.querySelector('[role="region"]')).toBeNull();

    wrapper.unmount();
});
