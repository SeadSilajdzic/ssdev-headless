import { flushPromises, mount } from '@vue/test-utils';
import AppLayout from '@headless/layouts/AppLayout.vue';
import { setPageProps } from '../setup';

const navigation = [
    { group: 'main', label: 'Main', items: [{ key: 'dashboard', label: 'Dashboard', url: '/dashboard', icon: 'layout-dashboard', active: true }] },
    { group: 'account', label: 'Account', items: [{ key: 'profile', label: 'Profile', url: '/user/profile', icon: 'user', active: false }] },
];

it('renders the app navigation and marks the active item', () => {
    setPageProps({ headless: { navigation } });
    const wrapper = mount(AppLayout, { props: { title: 'Dashboard' }, global: { stubs: { Head: true } } });
    expect(wrapper.get('a[aria-current="page"]').text()).toContain('Dashboard');
});

it('leaves the account links to the user menu and the settings pages', () => {
    setPageProps({ headless: { navigation } });
    const wrapper = mount(AppLayout, { props: { title: 'Dashboard' }, global: { stubs: { Head: true } } });
    expect(wrapper.find('a[href="/user/profile"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Account');
});

it('opens the mobile navigation from the menu button', async () => {
    setPageProps({ headless: { navigation } });
    mount(AppLayout, { props: { title: 'Dashboard' }, attachTo: document.body });
    document.querySelector<HTMLElement>('[aria-label="Open menu"]')!.click();
    await flushPromises();
    const drawer = document.querySelector('[role="dialog"]')?.textContent;
    expect(drawer).toContain('Dashboard');
    expect(drawer).not.toContain('Profile');
});

it('shows a toast for flash messages', async () => {
    setPageProps({ flash: { success: 'Saved.', error: null } });
    mount(AppLayout, { props: { title: 'Dashboard' }, attachTo: document.body, global: { stubs: { Head: true } } });
    await flushPromises();
    expect(document.body.textContent).toContain('Saved.');
});

async function openUserMenu(): Promise<void> {
    mount(AppLayout, { props: { title: 'Dashboard' }, attachTo: document.body });
    document.querySelector<HTMLElement>('[aria-label="User menu"]')!.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    await flushPromises();
}

it('lists the account links in the user menu', async () => {
    setPageProps({ headless: { navigation } });
    await openUserMenu();
    expect(document.querySelector('[role="menu"] a[href="/user/profile"]')).not.toBeNull();
});

it('shows the logout item when a logout url is shared', async () => {
    await openUserMenu();
    expect(document.body.textContent).toContain('Log out');
});

it('hides the logout item when no logout url is shared', async () => {
    setPageProps({ headless: { logoutUrl: null } });
    await openUserMenu();
    expect(document.body.textContent).not.toContain('Log out');
});
