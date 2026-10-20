import { translate } from '@headless/i18n';
import type { Translations } from '@headless/types';

const translations = { common: { actions: { save: 'Save' }, greeting: 'Hi :name, :name_full' } };

it('translates nested keys', () => expect(translate(translations, 'common.actions.save')).toBe('Save'));

it('replaces longer placeholders first', () =>
    expect(translate(translations, 'common.greeting', { name: 'Ada', name_full: 'Ada Lovelace' })).toBe('Hi Ada, Ada Lovelace'));

it('returns the key when the translation is missing', () => expect(translate(translations, 'common.nope')).toBe('common.nope'));

it('returns the key when it points to a group', () => expect(translate(translations, 'common.actions')).toBe('common.actions'));

it('returns the key when a translation value is null', () => {
    const withNull = { common: { empty: null } } as unknown as Translations;
    expect(translate(withNull, 'common.empty.deeper')).toBe('common.empty.deeper');
});
