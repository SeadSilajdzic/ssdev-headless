<script setup lang="ts">
import { CloudUpload } from '@lucide/vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ accept: string[]; maxSizeKb: number; hint?: string }>();

const emit = defineEmits<{ error: [message: string] }>();

const model = defineModel<File | null>({ default: null });
const t = useTrans();

function select(file: File | undefined): void {
    if (!file) {
        return;
    }

    if (!props.accept.includes(file.type)) {
        emit('error', t('common.upload.invalid_type'));
        return;
    }

    if (file.size > props.maxSizeKb * 1024) {
        emit('error', t('common.upload.too_large', { size: props.maxSizeKb }));
        return;
    }

    model.value = file;
}
</script>

<template>
    <label class="block cursor-pointer" @dragover.prevent @drop.prevent="select($event.dataTransfer?.files[0])">
        <div class="rounded-xl border-2 border-dashed border-ink-200 p-8 text-center transition hover:border-mint-400 hover:bg-mint-50/30">
            <CloudUpload class="mx-auto h-8 w-8 text-ink-400" aria-hidden="true" />
            <div class="mt-3 text-sm font-semibold text-mint-700">{{ t('common.upload.drop') }}</div>
            <div v-if="hint" class="mt-1 text-xs text-ink-400">{{ hint }}</div>
        </div>
        <input type="file" class="sr-only" :accept="accept.join(',')" @change="select(($event.target as HTMLInputElement).files?.[0])" />
    </label>
</template>
