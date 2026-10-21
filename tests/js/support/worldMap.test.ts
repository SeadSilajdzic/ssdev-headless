import { WORLD_PATH } from '@headless/components/settings/worldMap';

it('never draws a line across the whole map', () => {
    for (const ring of WORLD_PATH.split('M').filter(Boolean)) {
        const xs = ring.replace('Z', '').split('L').map((point) => Number(point.split(' ')[0]));

        for (let index = 1; index < xs.length; index++) {
            expect(Math.abs(xs[index] - xs[index - 1])).toBeLessThan(180);
        }
    }
});
