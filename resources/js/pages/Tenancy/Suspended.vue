<script setup lang="ts">
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const t = useTrans();
</script>

<template>
    <AuthLayout :title="t('tenancy.suspended.title')">
        <p class="text-ink-600">{{ t('tenancy.suspended.description') }}</p>
    </AuthLayout>
</template>
