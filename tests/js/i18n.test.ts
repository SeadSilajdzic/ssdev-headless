import { translate } from '@headless/i18n';

const translations = { common: { actions: { save: 'Save' }, greeting: 'Hi :name, :name_full' } };

it('translates nested keys', () => expect(translate(translations, 'common.actions.save')).toBe('Save'));

it('replaces longer placeholders first', () =>
    expect(translate(translations, 'common.greeting', { name: 'Ada', name_full: 'Ada Lovelace' })).toBe('Hi Ada, Ada Lovelace'));

it('returns the key when the translation is missing', () => expect(translate(translations, 'common.nope')).toBe('common.nope'));

it('returns the key when it points to a group', () => expect(translate(translations, 'common.actions')).toBe('common.actions'));
