import { mount } from '@vue/test-utils';
import AcceptInvitation from '@headless/pages/Users/AcceptInvitation.vue';

it('asks the invited email for a new password twice', () => {
    const wrapper = mount(AcceptInvitation, {
        props: { submitUrl: '/invitation', token: 'abc', email: 'ada@example.com' },
        global: { stubs: { AuthLayout: { template: '<div><slot /></div>' } } },
    });

    expect(wrapper.get<HTMLInputElement>('input[type="email"]').element.value).toBe('ada@example.com');
    expect(wrapper.findAll('input[type="password"]')).toHaveLength(2);
});
