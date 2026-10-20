<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';
import {
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
} from 'reka-ui';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';

defineProps<{
    title: string;
    description: string;
    confirmLabel: string;
    cancelLabel?: string;
    destructive?: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{ confirm: [] }>();

const open = defineModel<boolean>('open', { default: false });
const t = useTrans();
</script>

<template>
    <AlertDialogRoot v-model:open="open">
        <AlertDialogPortal>
            <AlertDialogOverlay class="fixed inset-0 z-50 bg-ink-900/40 backdrop-blur-sm" />
            <AlertDialogContent
                class="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-card border border-ink-100 bg-white p-6 shadow-pop"
            >
                <div v-if="destructive" class="mb-4 grid h-12 w-12 place-items-center rounded-full bg-rose-100">
                    <TriangleAlert class="h-6 w-6 text-rose-700" aria-hidden="true" />
                </div>
                <AlertDialogTitle class="font-display text-xl font-bold">{{ title }}</AlertDialogTitle>
                <AlertDialogDescription class="mt-2 text-sm text-ink-500">{{ description }}</AlertDialogDescription>
                <div class="mt-6 flex justify-end gap-2">
                    <AlertDialogCancel as-child>
                        <BaseButton variant="ghost">{{ cancelLabel ?? t('common.actions.cancel') }}</BaseButton>
                    </AlertDialogCancel>
                    <BaseButton :variant="destructive ? 'destructive' : 'primary'" :loading="loading" @click="emit('confirm')">
                        {{ confirmLabel }}
                    </BaseButton>
                </div>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
