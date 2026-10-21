import type { NavigationSection } from '@headless/types';

// Account links live in the user menu and the settings pages, not in the app sidebar.
export const ACCOUNT_GROUP = 'account';

export function appSections(sections: NavigationSection[]): NavigationSection[] {
    return sections.filter((section) => section.group !== ACCOUNT_GROUP);
}

export function accountLinks(sections: NavigationSection[]): NavigationSection['items'] {
    return sections.find((section) => section.group === ACCOUNT_GROUP)?.items ?? [];
}
