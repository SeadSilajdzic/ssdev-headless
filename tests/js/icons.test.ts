import { Circle, Star } from '@lucide/vue';
import { registerNavigationIcon, resolveNavigationIcon } from '@headless/icons';

it('falls back to a generic icon', () => expect(resolveNavigationIcon('unknown-icon')).toBe(Circle));

it('resolves registered icons', () => {
    registerNavigationIcon('star', Star);
    expect(resolveNavigationIcon('star')).toBe(Star);
});
