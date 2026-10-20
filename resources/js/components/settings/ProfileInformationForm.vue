<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ mustVerifyEmail: boolean; updateUrl: string; verificationUrl: string }>();

const t = useTrans();
const page = usePage();
const user = computed(() => page.props.auth.user);
const form = useForm({ name: user.value?.name ?? '', email: user.value?.email ?? '', current_password: '' });
const changesEmail = computed(() => form.email.trim().toLowerCase() !== user.value?.email);
const resending = ref(false);

function submit(): void {
    form.put(props.updateUrl, {
        preserveScroll: true,
        // The current email stays active until the new one is confirmed, so the form shows it again.
        onSuccess: () => form.defaults({ name: user.value?.name ?? '', email: user.value?.email ?? '', current_password: '' }).reset(),
    });
}

function resendVerification(): void {
    router.post(props.verificationUrl, {}, {
        preserveScroll: true,
        onStart: () => (resending.value = true),
        onFinish: () => (resending.value = false),
    });
}
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.name')" :error="form.errors.name" required>
            <BaseInput :id="id" v-model="form.name" autocomplete="name" :invalid="invalid" :aria-describedby="describedBy" />
        </FormField>

        <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.email')" :error="form.errors.email" required>
            <BaseInput :id="id" v-model="form.email" type="email" autocomplete="email" :invalid="invalid" :aria-describedby="describedBy" />
        </FormField>

        <p v-if="mustVerifyEmail && user && !user.email_verified" class="text-sm text-ink-600">
            {{ t('settings.profile.unverified') }}
            <button
                type="button"
                data-test="resend-verification"
                class="font-semibold text-mint-700 underline hover:text-mint-800 disabled:opacity-50"
                :disabled="resending"
                @click="resendVerification"
            >
                {{ t('settings.profile.resend_verification') }}
            </button>
        </p>

        <FormField
            v-if="changesEmail"
            v-slot="{ id, describedBy, invalid }"
            :label="t('auth.fields.current_password')"
            :hint="t('settings.profile.current_password_hint')"
            :error="form.errors.current_password"
            required
        >
            <PasswordInput :id="id" v-model="form.current_password" :invalid="invalid" :aria-describedby="describedBy" />
        </FormField>

        <div class="flex justify-end">
            <BaseButton type="submit" :loading="form.processing">{{ t('settings.profile.save') }}</BaseButton>
        </div>
    </form>
</template>
