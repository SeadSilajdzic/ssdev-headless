import { mount } from '@vue/test-utils';
import ProfileInformationForm from '@headless/components/settings/ProfileInformationForm.vue';
import { setPageProps } from '../setup';

const props = { mustVerifyEmail: true, updateUrl: '/settings/profile', verificationUrl: '/email/verification-notification' };

it('asks for the current password only after the email changes', async () => {
    const wrapper = mount(ProfileInformationForm, { props });

    expect(wrapper.find('input[autocomplete="current-password"]').exists()).toBe(false);

    await wrapper.get('input[type="email"]').setValue('new@example.com');

    expect(wrapper.find('input[autocomplete="current-password"]').exists()).toBe(true);
});

it('offers a new verification email to unverified users', () => {
    setPageProps({ auth: { user: { id: 1, name: 'Ada Lovelace', email: 'ada@example.com', email_verified: false, avatar_url: null } } });

    expect(mount(ProfileInformationForm, { props }).find('button[data-test="resend-verification"]').exists()).toBe(true);
});

it('does not offer verification to verified users', () => {
    expect(mount(ProfileInformationForm, { props }).find('button[data-test="resend-verification"]').exists()).toBe(false);
});
