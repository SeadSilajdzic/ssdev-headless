<script setup lang="ts">
import { Copy, Download } from '@lucide/vue';
import { ref } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ codes: string[] }>();

const DOWNLOAD_NAME = 'recovery-codes.txt';

const t = useTrans();
const copied = ref(false);

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.codes.join('\n'));
        copied.value = true;
    } catch {
        // The clipboard needs a secure origin; the codes stay on screen to copy by hand.
    }
}

function download(): void {
    const url = URL.createObjectURL(new Blob([props.codes.join('\n')], { type: 'text/plain' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = DOWNLOAD_NAME;
    link.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div>
        <p class="mb-3 text-sm text-ink-600">{{ t('two_factor.recovery_codes.description') }}</p>
        <ul class="grid gap-1 rounded-xl bg-ink-900 p-4 font-mono text-sm text-mint-300 sm:grid-cols-2" data-test="recovery-codes">
            <li v-for="code in codes" :key="code">{{ code }}</li>
        </ul>
        <div class="mt-3 flex flex-wrap gap-2">
            <BaseButton variant="ghost" size="sm" data-test="copy-codes" @click="copy">
                <Copy class="h-3.5 w-3.5" aria-hidden="true" />
                {{ copied ? t('two_factor.recovery_codes.copied') : t('two_factor.recovery_codes.copy') }}
            </BaseButton>
            <BaseButton variant="ghost" size="sm" data-test="download-codes" @click="download">
                <Download class="h-3.5 w-3.5" aria-hidden="true" />
                {{ t('two_factor.recovery_codes.download') }}
            </BaseButton>
        </div>
    </div>
</template>
