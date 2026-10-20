<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useTrans } from '@headless/i18n';

defineProps<{ title: string; description?: string }>();

const open = defineModel<boolean>('open', { default: false });
const t = useTrans();
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-ink-900/40 backdrop-blur-sm" />
            <DialogContent
                class="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-card border border-ink-100 bg-white p-6 shadow-pop"
            >
                <div class="mb-4 flex items-center justify-between">
                    <DialogTitle class="font-display text-xl font-bold">{{ title }}</DialogTitle>
                    <DialogClose class="text-ink-400 hover:text-ink-700" :aria-label="t('common.actions.close')">
                        <X class="h-5 w-5" aria-hidden="true" />
                    </DialogClose>
                </div>
                <DialogDescription v-if="description" class="mb-4 text-sm text-ink-500">{{ description }}</DialogDescription>
                <slot />
                <div v-if="$slots.footer" class="flex justify-end gap-2 pt-6">
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
