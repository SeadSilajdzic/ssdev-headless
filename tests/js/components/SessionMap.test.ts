import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import type { BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';
import SessionMap from '@headless/components/settings/SessionMap.vue';

const sarajevo = { city: 'Sarajevo', country: 'Bosnia and Herzegovina', latitude: 43.85, longitude: 18.41 };
const berlin = { city: 'Berlin', country: 'Germany', latitude: 52.52, longitude: 13.4 };

const sessions: BrowserSessionItem[] = [
    { id: 'here', platform: 'Windows', browser: 'Chrome', ip_address: '203.0.113.7', last_active: 'Active now', is_current: true, logout_url: '/s/here', location: sarajevo },
    { id: 'phone', platform: 'iOS', browser: 'Safari', ip_address: '198.51.100.4', last_active: '2 hours ago', is_current: false, logout_url: '/s/phone', location: berlin },
    { id: 'laptop', platform: 'macOS', browser: 'Safari', ip_address: '198.51.100.5', last_active: '3 days ago', is_current: false, logout_url: '/s/laptop', location: berlin },
];

function mountMap(selected: string | null = null) {
    return mount(SessionMap, { props: { sessions, selected }, attachTo: document.body });
}

afterEach(() => {
    document.body.innerHTML = '';
});

it('renders one point per place', () => {
    expect(mountMap().findAll('[data-test="map-point"]')).toHaveLength(2);
});

it('marks the current device', () => {
    const current = mountMap().findAll('[data-test="map-point"]').filter((point) => point.attributes('data-current') === 'true');

    expect(current).toHaveLength(1);
    expect(current[0].attributes('aria-label')).toContain('Sarajevo');
});

it('selects a session when its point opens', async () => {
    const wrapper = mountMap();

    await wrapper.findAll('[data-test="map-point"]').find((point) => point.attributes('aria-label')?.includes('Berlin'))?.trigger('click');

    expect(wrapper.emitted('update:selected')?.[0]).toEqual(['phone']);
});

it('offers no log out for the current device', async () => {
    mountMap('here');
    await nextTick();

    expect(document.querySelector('[data-test="map-popup"]')?.textContent).toContain('Sarajevo');
    expect(document.querySelectorAll('[data-test="map-logout"]')).toHaveLength(0);
});

it('lists every session of a place with its own log out', async () => {
    const wrapper = mountMap('phone');
    await nextTick();

    const buttons = document.querySelectorAll<HTMLButtonElement>('[data-test="map-logout"]');
    expect(buttons).toHaveLength(2);

    buttons[1].click();
    expect(wrapper.emitted('logout')?.[0]).toEqual([sessions[2]]);
});
