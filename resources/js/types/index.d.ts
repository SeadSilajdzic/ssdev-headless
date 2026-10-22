export interface AuthUser {
    id: number;
    name: string;
    email: string;
    email_verified: boolean;
    avatar_url: string | null;
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

export interface TeamSummary {
    id: number;
    name: string;
}

export interface TeamSwitcherProps {
    current: TeamSummary | null;
    all: TeamSummary[];
    switchUrl: string;
    createUrl: string;
    settingsUrl: string | null;
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
        honeypot: string;
    };
    flash: { success: string | null; error: string | null };
    // Shared only with the teams feature.
    teams?: TeamSwitcherProps | null;
}

export type PageMap = Record<string, () => Promise<unknown>>;
