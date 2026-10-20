import { scorePassword } from '@headless/support/passwordStrength';

it.each([
    ['', 0],
    ['password', 1],
    ['Password', 2],
    ['Password1', 3],
    ['Password1!', 4],
])('scores %s as %i', (password, score) => expect(scorePassword(password)).toBe(score));
