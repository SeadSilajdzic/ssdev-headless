import { defineComponent, reactive } from 'vue';
import type { HeadlessSharedProps } from '@headless/types';

export const fixtureTranslations = {
    common: {
        actions: { save: 'Save', cancel: 'Cancel', confirm: 'Confirm', close: 'Close', delete: 'Delete', back: 'Back', log_out: 'Log out' },
        password: {
            show: 'Show password',
            hide: 'Hide password',
            strength: { weak: 'Weak', fair: 'Fair', good: 'Good', strong: 'Strong' },
        },
        pagination: { previous: 'Previous', next: 'Next', showing: 'Showing :from to :to of :total results' },
        layout: { open_menu: 'Open menu', close_menu: 'Close menu', user_menu: 'User menu', breadcrumb: 'Breadcrumb' },
        table: { empty: 'No results found.', sort_ascending: 'Sort ascending', sort_descending: 'Sort descending' },
        upload: {
            drop: 'Click to upload or drag and drop',
            too_large: 'The file may not be greater than :size KB.',
            invalid_type: 'This file type is not allowed.',
        },
        toast: { dismiss: 'Dismiss' },
    },
    auth: {
        fields: {
            name: 'Name',
            email: 'Email',
            password: 'Password',
            password_confirmation: 'Confirm password',
            current_password: 'Current password',
            remember: 'Remember me',
        },
        login: {
            title: 'Welcome back',
            subtitle: 'Sign in to pick up where you left off.',
            submit: 'Sign in',
            forgot_password: 'Forgot password?',
            no_account: 'No account yet?',
            register: 'Create one',
        },
    },
    navigation: {
        groups: { main: 'Main', account: 'Account', administration: 'Administration' },
        dashboard: 'Dashboard',
    },
    errors: {
        '404': {
            title: 'This page went off the chart.',
            description: 'The link is broken, the page moved, or it never existed. Either way — nothing to see here.',
        },
        back_home: 'Back to dashboard',
    },
    settings: {
        sessions: {
            this_device: 'This device',
            unknown: 'Unknown device',
            log_out: 'Log out',
            unknown_location: 'Unknown location',
            map_label: 'Map of your signed-in sessions',
            place_session: ':place: 1 session',
            place_sessions: ':place: :count sessions',
        },
    },
};

type DeepPartial<T> = { [K in keyof T]?: T[K] extends object ? DeepPartial<T[K]> : T[K] };

function defaultProps(): HeadlessSharedProps {
    return {
        auth: { user: { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', email_verified: true, avatar_url: null }, permissions: [] },
        headless: {
            appName: 'Laravel',
            locale: 'en',
            features: [],
            navigation: [],
            translations: structuredClone(fixtureTranslations),
            logoutUrl: '/logout',
            honeypot: 'my_name',
        },
        flash: { success: null, error: null },
    };
}

const page = reactive({ props: defaultProps() });

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function merge(target: Record<string, unknown>, source: Record<string, unknown>): void {
    for (const [key, value] of Object.entries(source)) {
        if (isPlainObject(value) && isPlainObject(target[key])) {
            merge(target[key] as Record<string, unknown>, value);
        } else {
            target[key] = value;
        }
    }
}

export function setPageProps(partial: DeepPartial<HeadlessSharedProps>): void {
    merge(page.props as unknown as Record<string, unknown>, partial as Record<string, unknown>);
}

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => page,
    // The real Head needs a mounted Inertia app to provide its head manager.
    Head: defineComponent({ name: 'Head', render: () => null }),
}));

globalThis.ResizeObserver ??= class {
    observe(): void {}
    unobserve(): void {}
    disconnect(): void {}
};

beforeEach(() => {
    page.props = defaultProps();

    if (typeof document !== 'undefined') {
        document.body.innerHTML = '';
    }
});
