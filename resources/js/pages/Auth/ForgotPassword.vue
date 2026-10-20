<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import HoneypotField from '@headless/components/auth/HoneypotField.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useHoneypot } from '@headless/composables/useHoneypot';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ submitUrl: string; loginUrl: string; status: string | null }>();

const t = useTrans();
const trap = ref('');
const withHoneypot = useHoneypot(trap);
const form = useForm({ email: '' });

function submit(): void {
    form.transform(withHoneypot).post(props.submitUrl);
}
</script>

<template>
    <AuthLayout :title="t('auth.passwords.forgot_title')" :subtitle="t('auth.passwords.forgot_subtitle')">
        <BaseAlert v-if="status" variant="success" class="mb-6">{{ status }}</BaseAlert>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <HoneypotField v-model="trap" />

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.email')" :error="form.errors.email" required>
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="email" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.passwords.send_link') }}</BaseButton>
        </form>

        <p class="mt-8 text-center text-sm text-ink-500">
            {{ t('auth.passwords.remembered') }}
            <Link :href="loginUrl" class="font-semibold text-mint-700 hover:text-mint-800">{{ t('auth.passwords.back_to_login') }}</Link>
        </p>
    </AuthLayout>
</template>
