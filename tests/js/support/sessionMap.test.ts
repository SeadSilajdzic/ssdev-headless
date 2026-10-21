import type { BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';
import { groupByPlace, projectPoint } from '@headless/support/sessionMap';

function session(id: string, location: BrowserSessionItem['location'], isCurrent = false): BrowserSessionItem {
    return { id, platform: 'Windows', browser: 'Chrome', ip_address: null, last_active: 'now', is_current: isCurrent, logout_url: `/s/${id}`, location };
}

const sarajevo = { city: 'Sarajevo', country: 'Bosnia and Herzegovina', latitude: 43.85, longitude: 18.41 };
const berlin = { city: 'Berlin', country: 'Germany', latitude: 52.52, longitude: 13.4 };

it('projects coordinates onto the map', () => {
    expect(projectPoint(0, 0)).toEqual({ x: 180, y: 84 });

    const point = projectPoint(43.85, 18.41);
    expect(point.x).toBeCloseTo(198.41);
    expect(point.y).toBeCloseTo(40.15);
});

it('keeps far north and south points on the map', () => {
    expect(projectPoint(89, 0).y).toBe(0);
    expect(projectPoint(-80, 0).y).toBe(144);
});

it('groups sessions from one city into one point', () => {
    const places = groupByPlace([session('a', berlin), session('b', berlin), session('c', sarajevo, true)]);

    expect(places).toHaveLength(2);
    expect(places.find((place) => place.label === 'Berlin, Germany')?.sessions.map((item) => item.id)).toEqual(['a', 'b']);
    expect(places.find((place) => place.label === 'Sarajevo, Bosnia and Herzegovina')?.hasCurrent).toBe(true);
});

it('labels a place without a city by its country', () => {
    const [place] = groupByPlace([session('a', { ...berlin, city: null })]);

    expect(place.label).toBe('Germany');
});

it('leaves sessions without a location off the map', () => {
    expect(groupByPlace([session('a', null), session('b', berlin)]).flatMap((place) => place.sessions.map((item) => item.id))).toEqual(['b']);
});
