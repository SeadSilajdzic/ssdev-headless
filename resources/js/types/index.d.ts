export interface AuthUser {
    id: number;
    name: string;
    email: string;
    email_verified: boolean;
}

export interface NavigationLink {
    key: string;
    label: string;
    url: string;
    icon: string;
    active: boolean;
}

export interface NavigationSection {
    group: string;
    label: string;
    items: NavigationLink[];
}

export interface Translations {
    [key: string]: string | Translations;
}

export interface HeadlessSharedProps {
    auth: { user: AuthUser | null; permissions: string[] };
    headless: {
        appName: string;
        locale: string;
        features: string[];
        navigation: NavigationSection[];
        translations: Translations;
        logoutUrl: string | null;
    };
    flash: { success: string | null; error: string | null };
}

export type PageMap = Record<string, () => Promise<unknown>>;
