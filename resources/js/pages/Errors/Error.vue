<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ status: number }>();

const t = useTrans();
</script>

<template>
    <Head :title="t(`errors.${props.status}.title`)" />
    <div class="relative grid min-h-screen place-items-center overflow-hidden bg-mint-50 px-4 text-ink-900">
        <div class="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-mint-200/60 blur-3xl" aria-hidden="true" />
        <div class="absolute -bottom-40 -left-20 h-[400px] w-[400px] rounded-full bg-mint-300/40 blur-3xl" aria-hidden="true" />
        <div class="relative max-w-lg text-center">
            <div class="font-display text-[120px] leading-none font-bold tracking-tight text-mint-700 sm:text-[160px]">{{ status }}</div>
            <h1 class="mt-4 font-display text-3xl font-bold">{{ t(`errors.${props.status}.title`) }}</h1>
            <p class="mt-3 text-ink-500">{{ t(`errors.${props.status}.description`) }}</p>
            <div class="mt-8 flex justify-center">
                <BaseButton href="/">
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    {{ t('errors.back_home') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
