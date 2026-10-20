<script setup lang="ts">
import { Check, ChevronDown } from '@lucide/vue';
import {
    SelectContent,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectPortal,
    SelectRoot,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'reka-ui';
import { inputClasses } from '@headless/components/ui/inputClasses';

defineProps<{
    options: { value: string; label: string }[];
    placeholder?: string;
    invalid?: boolean;
}>();

const model = defineModel<string | null>({ default: null });
</script>

<template>
    <SelectRoot v-model="model">
        <SelectTrigger :aria-invalid="invalid ? 'true' : undefined" :class="[inputClasses(invalid), 'flex items-center justify-between text-left']">
            <SelectValue :placeholder="placeholder" />
            <ChevronDown class="h-4 w-4 text-ink-400" aria-hidden="true" />
        </SelectTrigger>
        <SelectPortal>
            <SelectContent position="popper" :side-offset="6" class="z-50 min-w-[var(--reka-select-trigger-width)] rounded-xl border border-ink-100 bg-white p-1 shadow-pop">
                <SelectViewport>
                    <SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm text-ink-700 outline-none data-[highlighted]:bg-ink-50"
                    >
                        <SelectItemText>{{ option.label }}</SelectItemText>
                        <SelectItemIndicator>
                            <Check class="h-4 w-4 text-mint-600" aria-hidden="true" />
                        </SelectItemIndicator>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>
