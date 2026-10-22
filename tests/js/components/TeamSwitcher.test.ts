import { router } from '@inertiajs/vue3';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import TeamSwitcher from '@headless/components/teams/TeamSwitcher.vue';
import { setPageProps } from '../setup';

enableAutoUnmount(afterEach);

const teams = {
    current: { id: 1, name: 'Research' },
    all: [
        { id: 1, name: 'Research' },
        { id: 2, name: 'Sales' },
    ],
    switchUrl: '/current-team',
    createUrl: '/teams/create',
    settingsUrl: '/teams/1',
};

async function openSwitcher() {
    const wrapper = mount(TeamSwitcher, { attachTo: document.body });
    await wrapper.get('[aria-label="Switch team"]').trigger('keydown', { key: 'Enter' });
    await flushPromises();

    return wrapper;
}

it('shows the current team and lists the others', async () => {
    setPageProps({ teams, headless: { translations: { teams: { switcher: { label: 'Switch team', settings: 'Team settings', create: 'Create team', no_team: 'No team' } } } } });

    const wrapper = await openSwitcher();

    expect(wrapper.text()).toContain('Research');
    expect(document.body.textContent).toContain('Sales');
    expect(document.querySelector('a[href="/teams/create"]')).not.toBeNull();
});

it('switches to the team that is picked', async () => {
    setPageProps({ teams, headless: { translations: { teams: { switcher: { label: 'Switch team', settings: 'Team settings', create: 'Create team', no_team: 'No team' } } } } });
    const switchTeam = vi.spyOn(router, 'put').mockImplementation(() => {});

    await openSwitcher();
    const sales = [...document.querySelectorAll<HTMLElement>('[data-test="switch-team"]')].find((item) => item.textContent?.includes('Sales'));
    sales!.click();
    await flushPromises();

    expect(switchTeam).toHaveBeenCalledWith('/current-team', { team_id: 2 }, expect.anything());
    switchTeam.mockRestore();
});

it('renders nothing without teams', () => {
    expect(mount(TeamSwitcher).html()).toBe('<!--v-if-->');
});
