<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import PasswordStrength from '@headless/components/ui/PasswordStrength.vue';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ team: { name: string }; email: string; submitUrl: string }>();

const t = useTrans();
const page = usePage();
const form = useForm({ name: '', password: '', password_confirmation: '' });
// The address is not a form field; the server reports an existing account on it.
const emailError = computed(() => page.props.errors.email);

function submit(): void {
    form.post(props.submitUrl, { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <AuthLayout :title="t('teams.invitation_account.title', { team: team.name })" :subtitle="t('teams.invitation_account.subtitle')">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField v-slot="{ id, describedBy }" :label="t('auth.fields.email')" :error="emailError">
                <BaseInput :id="id" :model-value="email" type="email" autocomplete="username" readonly :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('teams.invitation_account.name')" :error="form.errors.name" required>
                <BaseInput :id="id" v-model="form.name" autocomplete="name" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
                <PasswordStrength class="mt-2" :password="form.password" />
            </FormField>

            <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password_confirmation')" :error="form.errors.password_confirmation" required>
                <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('teams.invitation_account.submit') }}</BaseButton>
        </form>
    </AuthLayout>
</template>
