import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import BrowserSessions, { type BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';
import SessionMap from '@headless/components/settings/SessionMap.vue';

const sessions: BrowserSessionItem[] = [
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

const located = [
    { ...sessions[0], ip_address: '203.0.113.7', location: { city: 'Sarajevo', country: 'Bosnia and Herzegovina', latitude: 43.85, longitude: 18.41 } },
    { ...sessions[1], ip_address: '10.0.0.2', location: null },
];

function mountSessions(items: BrowserSessionItem[]) {
    return mount(BrowserSessions, { props: { supported: true, sessions: items, logoutOthersUrl: '/settings/security/sessions' }, attachTo: document.body });
}

it('shows the map only when a session has a location', () => {
    expect(mountSessions(sessions).findComponent(SessionMap).exists()).toBe(false);
    expect(mountSessions(located).findComponent(SessionMap).exists()).toBe(true);
});

it('shows the city next to each device', () => {
    const rows = mountSessions(located).findAll('[data-test="session-row"]');

    expect(rows[0].text()).toContain('Sarajevo, Bosnia and Herzegovina');
    expect(rows[1].text()).toContain('Unknown location');
});

it('shows no location text while locations are off', () => {
    expect(mountSessions(sessions).text()).not.toContain('Unknown location');
});

it('highlights the row of the selected point', async () => {
    const wrapper = mountSessions(located);

    wrapper.findComponent(SessionMap).vm.$emit('update:selected', 'a');
    await nextTick();

    expect(wrapper.findAll('[data-test="session-row"]')[0].attributes('data-selected')).toBe('true');
});
