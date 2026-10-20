<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ resendUrl: string; profileUrl: string | null; email: string | null; status: string | null }>();

const t = useTrans();
const page = usePage();
const form = useForm({});

function resend(): void {
    form.post(props.resendUrl);
}

function logout(): void {
    if (page.props.headless.logoutUrl) {
        router.post(page.props.headless.logoutUrl);
    }
}
</script>

<template>
    <AuthLayout :title="t('auth.verification.title')">
        <p class="text-ink-600">{{ t('auth.verification.intro', { email: email ?? '' }) }}</p>
        <p class="mt-3 text-sm text-ink-500">{{ t('auth.verification.help') }}</p>

        <BaseAlert v-if="status" variant="success" class="mt-6">{{ status }}</BaseAlert>

        <div class="mt-8 flex flex-wrap items-center gap-3">
            <BaseButton :loading="form.processing" @click="resend">{{ t('auth.verification.resend') }}</BaseButton>
            <BaseButton v-if="page.props.headless.logoutUrl" variant="ghost" @click="logout">{{ t('common.actions.log_out') }}</BaseButton>
        </div>

        <p v-if="profileUrl" class="mt-8 text-sm text-ink-500">
            {{ t('auth.verification.wrong_email') }}
            <Link :href="profileUrl" class="font-semibold text-mint-700 hover:text-mint-800">{{ t('auth.verification.update_address') }}</Link>
        </p>
    </AuthLayout>
</template>
