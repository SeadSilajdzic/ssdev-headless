<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavigationLinks from '@headless/components/layout/NavigationLinks.vue';
import AppLayout from '@headless/layouts/AppLayout.vue';
import { ACCOUNT_GROUP } from '@headless/support/navigation';

defineProps<{ title: string; breadcrumbs?: { label: string; href?: string }[] }>();

const page = usePage();
const accountSections = computed(() =>
    page.props.headless.navigation.filter((section) => section.group === ACCOUNT_GROUP).map((section) => ({ ...section, group: 'main' })),
);
</script>

<template>
    <AppLayout :title="title" :breadcrumbs="breadcrumbs">
        <div class="grid max-w-6xl grid-cols-1 gap-6 lg:grid-cols-12">
            <nav class="lg:col-span-3">
                <NavigationLinks :sections="accountSections" />
            </nav>
            <div class="space-y-6 lg:col-span-9">
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
