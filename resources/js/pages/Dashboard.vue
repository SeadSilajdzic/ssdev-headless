<script setup lang="ts">
import EmptyState from '@headless/components/ui/EmptyState.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const t = useTrans();
</script>

<template>
    <AppLayout :title="t('navigation.dashboard')">
        <EmptyState :title="t('dashboard.empty.title')" :description="t('dashboard.empty.description')" />
    </AppLayout>
</template>
