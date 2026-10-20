<script setup lang="ts">
import { CircleCheck, CircleX, X } from '@lucide/vue';
import { ToastClose, ToastDescription, ToastProvider, ToastRoot, ToastViewport } from 'reka-ui';
import { useToast } from '@headless/composables/useToast';
import { useTrans } from '@headless/i18n';

const { toasts, dismiss } = useToast();
const t = useTrans();
</script>

<template>
    <!-- useToast owns the timing, so Reka's own timer is disabled. -->
    <ToastProvider :duration="Infinity" swipe-direction="right">
        <ToastRoot
            v-for="toast in toasts"
            :key="toast.id"
            :open="true"
            class="flex w-80 items-start gap-3 rounded-xl border bg-white p-4 shadow-pop"
            :class="toast.type === 'success' ? 'border-mint-300' : 'border-rose-300'"
            @update:open="(open: boolean) => !open && dismiss(toast.id)"
        >
            <CircleCheck v-if="toast.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0 text-mint-700" aria-hidden="true" />
            <CircleX v-else class="mt-0.5 h-5 w-5 shrink-0 text-rose-700" aria-hidden="true" />
            <ToastDescription class="flex-1 text-sm font-medium text-ink-900">{{ toast.message }}</ToastDescription>
            <ToastClose :aria-label="t('common.toast.dismiss')" class="text-ink-400 hover:text-ink-700">
                <X class="h-4 w-4" aria-hidden="true" />
            </ToastClose>
        </ToastRoot>
        <ToastViewport class="fixed right-6 bottom-6 z-50 flex flex-col gap-2 outline-none" />
    </ToastProvider>
</template>
