<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import HoneypotField from '@headless/components/auth/HoneypotField.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCheckbox from '@headless/components/ui/BaseCheckbox.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useHoneypot } from '@headless/composables/useHoneypot';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{
    loginUrl: string;
    passwordRequestUrl: string | null;
    registerUrl: string | null;
    status: string | null;
}>();

const t = useTrans();
const trap = ref('');
const withHoneypot = useHoneypot(trap);
const form = useForm({ email: '', password: '', remember: false });

function submit(): void {
    form.transform(withHoneypot).post(props.loginUrl, { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout :title="t('auth.login.title')" :subtitle="t('auth.login.subtitle')">
        <BaseAlert v-if="status" variant="success" class="mb-6">{{ status }}</BaseAlert>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <HoneypotField v-model="trap" />

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.email')" :error="form.errors.email" required>
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="username" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                <PasswordInput :id="id" v-model="form.password" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <div class="flex items-center justify-between gap-4">
                <BaseCheckbox v-model="form.remember" :label="t('auth.fields.remember')" />
                <Link v-if="passwordRequestUrl" :href="passwordRequestUrl" class="text-sm font-semibold text-mint-700 hover:text-mint-800">
                    {{ t('auth.login.forgot_password') }}
                </Link>
            </div>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.login.submit') }}</BaseButton>
        </form>

        <p v-if="registerUrl" class="mt-8 text-center text-sm text-ink-500">
            {{ t('auth.login.no_account') }}
            <Link :href="registerUrl" class="font-semibold text-mint-700 hover:text-mint-800">{{ t('auth.login.register') }}</Link>
        </p>
    </AuthLayout>
</template>
