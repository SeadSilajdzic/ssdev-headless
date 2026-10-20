import { mount } from '@vue/test-utils';
import Login from '@headless/pages/Auth/Login.vue';

const urls = { loginUrl: '/login', passwordRequestUrl: '/forgot-password', status: null };

it('links to registration only when it is available', () => {
    expect(mount(Login, { props: { ...urls, registerUrl: '/register' } }).find('a[href="/register"]').exists()).toBe(true);
    expect(mount(Login, { props: { ...urls, registerUrl: null } }).find('a[href="/register"]').exists()).toBe(false);
});

it('shows the status message', () => {
    expect(mount(Login, { props: { ...urls, registerUrl: null, status: 'Check your inbox.' } }).text()).toContain('Check your inbox.');
});
