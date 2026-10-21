import type { BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';

export interface SessionLocation {
    city: string | null;
    country: string | null;
    latitude: number;
    longitude: number;
}

export interface MapPlace {
    key: string;
    label: string;
    x: number;
    y: number;
    sessions: BrowserSessionItem[];
    hasCurrent: boolean;
}

// Same projection as WORLD_PATH: x = longitude + 180, y = 84 - latitude.
export const MAP_WIDTH = 360;
export const MAP_HEIGHT = 144;
const NORTH = 84;

const clamp = (value: number, max: number): number => Math.min(Math.max(value, 0), max);

export function projectPoint(latitude: number, longitude: number): { x: number; y: number } {
    return { x: clamp(longitude + 180, MAP_WIDTH), y: clamp(NORTH - latitude, MAP_HEIGHT) };
}

export function placeLabel(location: SessionLocation): string {
    return [location.city, location.country].filter(Boolean).join(', ');
}

export function deviceLabel(session: BrowserSessionItem): string {
    return [session.platform, session.browser].filter(Boolean).join(' · ');
}

// Sessions without a city are grouped by their coordinates, rounded to about 10 km.
function placeKey(location: SessionLocation): string {
    return location.city !== null
        ? `${location.city}|${location.country ?? ''}`
        : `${location.latitude.toFixed(1)}|${location.longitude.toFixed(1)}`;
}

export function groupByPlace(sessions: BrowserSessionItem[]): MapPlace[] {
    const places = new Map<string, MapPlace>();

    for (const session of sessions) {
        if (session.location === null) {
            continue;
        }

        const key = placeKey(session.location);
        const place = places.get(key) ?? {
            key,
            label: placeLabel(session.location),
            ...projectPoint(session.location.latitude, session.location.longitude),
            sessions: [],
            hasCurrent: false,
        };

        place.sessions.push(session);
        place.hasCurrent ||= session.is_current;
        places.set(key, place);
    }

    return [...places.values()];
}
