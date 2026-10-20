<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import PasswordStrength from '@headless/components/ui/PasswordStrength.vue';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ submitUrl: string; token: string; email: string }>();

const t = useTrans();
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });

function submit(): void {
    form.post(props.submitUrl, { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <AuthLayout :title="t('auth.passwords.reset_title')" :subtitle="t('auth.passwords.reset_subtitle')">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.email')" :error="form.errors.email" required>
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="username" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
                <PasswordStrength class="mt-2" :password="form.password" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password_confirmation')" :error="form.errors.password_confirmation" required>
                <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.passwords.reset_submit') }}</BaseButton>
        </form>
    </AuthLayout>
</template>
