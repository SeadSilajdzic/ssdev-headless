import { mount } from '@vue/test-utils';
import BrowserSessions from '@headless/components/settings/BrowserSessions.vue';

const sessions = [
    { id: 'a', platform: 'macOS', browser: 'Chrome', ip_address: '10.0.0.1', last_active: 'now', is_current: true, logout_url: '/settings/security/sessions/a', location: null },
    { id: 'b', platform: 'iOS', browser: 'Safari', ip_address: '10.0.0.2', last_active: '2 hours ago', is_current: false, logout_url: '/settings/security/sessions/b', location: null },
];

it('offers log out only for other devices', () => {
    const wrapper = mount(BrowserSessions, { props: { supported: true, sessions, logoutOthersUrl: '/settings/security/sessions' } });

    expect(wrapper.findAll('[data-test="logout-session"]')).toHaveLength(1);
});

it('explains when sessions are not supported', () => {
    const wrapper = mount(BrowserSessions, { props: { supported: false, sessions: [], logoutOthersUrl: '/settings/security/sessions' } });

    expect(wrapper.find('[role="alert"], [data-test="sessions-unsupported"]').exists()).toBe(true);
    expect(wrapper.findAll('[data-test="logout-session"]')).toHaveLength(0);
});
