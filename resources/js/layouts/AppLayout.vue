<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppSidebar from '@headless/components/layout/AppSidebar.vue';
import AppTopbar from '@headless/components/layout/AppTopbar.vue';
import MobileNav from '@headless/components/layout/MobileNav.vue';
import BaseBreadcrumb from '@headless/components/ui/BaseBreadcrumb.vue';
import ToastViewport from '@headless/components/ui/ToastViewport.vue';
import { useToast } from '@headless/composables/useToast';
import { appSections } from '@headless/support/navigation';

defineProps<{ title: string; breadcrumbs?: { label: string; href?: string }[] }>();

const page = usePage();
const toast = useToast();
const menuOpen = ref(false);
const sections = computed(() => appSections(page.props.headless.navigation));

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen bg-mint-50 text-ink-900">
        <AppSidebar class="hidden lg:flex" :sections="sections" />
        <MobileNav v-model:open="menuOpen" :title="page.props.headless.appName" :sections="sections" />

        <main class="flex min-w-0 flex-1 flex-col">
            <AppTopbar @open-menu="menuOpen = true">
                <template #actions><slot name="actions" /></template>
            </AppTopbar>

            <div class="flex flex-wrap items-center justify-between gap-2 px-4 pt-6 sm:px-8">
                <h1 class="font-display text-3xl font-bold tracking-tight">{{ title }}</h1>
                <BaseBreadcrumb v-if="breadcrumbs?.length" :items="breadcrumbs" />
            </div>

            <div class="p-4 sm:p-8">
                <slot />
            </div>
        </main>

        <ToastViewport />
    </div>
</template>
