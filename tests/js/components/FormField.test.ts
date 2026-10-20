import { mount } from '@vue/test-utils';
import FormField from '@headless/components/ui/FormField.vue';

it('links label, hint and error to the control', () => {
    const wrapper = mount(FormField, {
        props: { label: 'Email', error: 'Required', hint: 'Work email', id: 'email' },
        slots: {
            default: `<template #default="{ id, describedBy, invalid }"><input :id="id" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>`,
        },
    });
    expect(wrapper.get('label').attributes('for')).toBe('email');
    expect(wrapper.get('input').attributes('aria-describedby')).toBe('email-hint email-error');
    expect(wrapper.get('input').attributes('aria-invalid')).toBe('true');
    expect(wrapper.get('#email-error').attributes('role')).toBe('alert');
});
