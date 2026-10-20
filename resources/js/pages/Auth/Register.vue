<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import HoneypotField from '@headless/components/auth/HoneypotField.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import PasswordStrength from '@headless/components/ui/PasswordStrength.vue';
import { useHoneypot } from '@headless/composables/useHoneypot';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ submitUrl: string; loginUrl: string }>();

const t = useTrans();
const trap = ref('');
const withHoneypot = useHoneypot(trap);
const form = useForm({ name: '', email: '', password: '' });

function submit(): void {
    form.transform(withHoneypot).post(props.submitUrl, { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout :title="t('auth.register.title')" :subtitle="t('auth.register.subtitle')">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <HoneypotField v-model="trap" />

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.name')" :error="form.errors.name" required>
                <BaseInput :id="id" v-model="form.name" autocomplete="name" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.email')" :error="form.errors.email" required>
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="email" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
                <PasswordStrength class="mt-2" :password="form.password" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.register.submit') }}</BaseButton>
        </form>

        <p class="mt-8 text-center text-sm text-ink-500">
            {{ t('auth.register.have_account') }}
            <Link :href="loginUrl" class="font-semibold text-mint-700 hover:text-mint-800">{{ t('auth.register.login') }}</Link>
        </p>
    </AuthLayout>
</template>
