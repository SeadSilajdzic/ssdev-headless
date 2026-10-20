import { mount } from '@vue/test-utils';
import Register from '@headless/pages/Auth/Register.vue';

it('shows the password strength while typing', async () => {
    const wrapper = mount(Register, { props: { submitUrl: '/register', loginUrl: '/login' } });

    await wrapper.get('input[autocomplete="new-password"]').setValue('S3cure-password!');

    expect(wrapper.text()).toContain('Strong');
});
