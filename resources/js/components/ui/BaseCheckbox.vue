<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
import { useId } from 'vue';

defineProps<{ label?: string }>();

const model = defineModel<boolean>({ default: false });
const id = useId();
</script>

<template>
    <div class="flex items-center gap-2">
        <CheckboxRoot
            :id="id"
            v-model="model"
            class="grid h-4 w-4 shrink-0 place-items-center rounded border border-ink-300 bg-white data-[state=checked]:border-mint-600 data-[state=checked]:bg-mint-600"
        >
            <CheckboxIndicator>
                <Check class="h-3 w-3 text-white" aria-hidden="true" />
            </CheckboxIndicator>
        </CheckboxRoot>
        <label v-if="label || $slots.default" :for="id" class="cursor-pointer text-sm select-none">
            <slot>{{ label }}</slot>
        </label>
    </div>
</template>
