import { useCan } from '@headless/composables/useCan';
import { setPageProps } from '../setup';

it('checks the shared permissions', () => {
    setPageProps({ auth: { permissions: ['reports.view'] } });
    const can = useCan();

    expect(can('reports.view')).toBe(true);
    expect(can('reports.export')).toBe(false);
});
