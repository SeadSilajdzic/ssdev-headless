<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ submitUrl: string }>();

const t = useTrans();
const form = useForm({ password: '' });

function submit(): void {
    form.post(props.submitUrl, { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout :title="t('auth.confirm.title')" :subtitle="t('auth.confirm.subtitle')">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                <PasswordInput :id="id" v-model="form.password" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('auth.confirm.submit') }}</BaseButton>
        </form>
    </AuthLayout>
</template>
