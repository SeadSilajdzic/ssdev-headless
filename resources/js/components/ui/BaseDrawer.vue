<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useTrans } from '@headless/i18n';

withDefaults(defineProps<{ title: string; description?: string; side?: 'left' | 'right' }>(), { side: 'right' });

const open = defineModel<boolean>('open', { default: false });
const t = useTrans();
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-ink-900/40 backdrop-blur-sm" />
            <DialogContent
                class="fixed top-0 z-50 flex h-full w-full max-w-md flex-col border-ink-100 bg-white"
                :class="side === 'right' ? 'right-0 border-l' : 'left-0 border-r'"
            >
                <div class="flex items-center justify-between border-b border-ink-100 p-5">
                    <div>
                        <DialogTitle class="font-display text-xl font-bold">{{ title }}</DialogTitle>
                        <DialogDescription v-if="description" class="mt-0.5 text-xs text-ink-500">{{ description }}</DialogDescription>
                    </div>
                    <DialogClose class="text-ink-400 hover:text-ink-700" :aria-label="t('common.actions.close')">
                        <X class="h-5 w-5" aria-hidden="true" />
                    </DialogClose>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-ink-100 p-5">
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
