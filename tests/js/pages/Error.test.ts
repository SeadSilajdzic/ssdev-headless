import { mount } from '@vue/test-utils';
import ErrorPage from '@headless/pages/Errors/Error.vue';
import { fixtureTranslations } from '../setup';

it('shows the translated title for the status', () => {
    const wrapper = mount(ErrorPage, { props: { status: 404 }, global: { stubs: { Head: true } } });
    expect(wrapper.text()).toContain('404');
    expect(wrapper.text()).toContain(fixtureTranslations.errors['404'].title);
});
