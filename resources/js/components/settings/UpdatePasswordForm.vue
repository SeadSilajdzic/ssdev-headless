<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import PasswordStrength from '@headless/components/ui/PasswordStrength.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ updateUrl: string }>();

const t = useTrans();
const form = useForm({ current_password: '', password: '', password_confirmation: '' });

function submit(): void {
    form.put(props.updateUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.current_password')" :error="form.errors.current_password" required>
            <PasswordInput :id="id" v-model="form.current_password" :invalid="invalid" :aria-describedby="describedBy" />
        </FormField>

        <FormField v-slot="{ id, describedBy, invalid }" :label="t('settings.password.new')" :error="form.errors.password" required>
            <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
            <PasswordStrength class="mt-2" :password="form.password" />
        </FormField>

        <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password_confirmation')" :error="form.errors.password_confirmation" required>
            <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" :invalid="invalid" :aria-describedby="describedBy" />
        </FormField>

        <div class="flex justify-end">
            <BaseButton type="submit" :loading="form.processing">{{ t('settings.password.submit') }}</BaseButton>
        </div>
    </form>
</template>
