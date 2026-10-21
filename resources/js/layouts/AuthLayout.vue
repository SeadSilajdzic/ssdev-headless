<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@headless/components/layout/AppLogo.vue';
import ToastViewport from '@headless/components/ui/ToastViewport.vue';

defineProps<{ title: string; subtitle?: string }>();

const page = usePage();
</script>

<template>
    <Head :title="title" />
    <div class="grid min-h-screen bg-white text-ink-900 lg:grid-cols-2">
        <div class="flex flex-col px-6 py-8 sm:px-10 lg:px-16">
            <Link href="/" class="w-fit"><AppLogo /></Link>
            <div class="grid flex-1 place-items-center py-12">
                <div class="w-full max-w-md">
                    <h1 class="font-display text-display">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 text-ink-500">{{ subtitle }}</p>
                    <div class="mt-8">
                        <slot />
                    </div>
                </div>
            </div>
            <p class="text-xs text-ink-400">© {{ new Date().getFullYear() }} {{ page.props.headless.appName }}</p>
        </div>
        <div class="relative hidden overflow-hidden bg-mint-50 lg:block" aria-hidden="true">
            <div class="absolute -top-32 -right-32 h-[500px] w-[500px] rounded-full bg-mint-200/60 blur-3xl" />
            <div class="absolute -bottom-40 -left-20 h-[400px] w-[400px] rounded-full bg-mint-300/40 blur-3xl" />
            <div class="relative grid h-full place-items-center p-16">
                <span class="font-display text-5xl font-bold tracking-tight text-mint-900">{{ page.props.headless.appName }}</span>
            </div>
        </div>
    </div>
    <ToastViewport />
</template>
