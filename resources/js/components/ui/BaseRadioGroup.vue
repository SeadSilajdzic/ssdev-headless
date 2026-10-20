<script setup lang="ts">
import { RadioGroupIndicator, RadioGroupItem, RadioGroupRoot } from 'reka-ui';
import { useId } from 'vue';

defineProps<{ options: { value: string; label: string; description?: string }[] }>();

const model = defineModel<string>();
const id = useId();
</script>

<template>
    <RadioGroupRoot v-model="model" class="space-y-2">
        <div v-for="option in options" :key="option.value" class="flex items-start gap-2">
            <RadioGroupItem
                :id="`${id}-${option.value}`"
                :value="option.value"
                class="mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full border border-ink-300 bg-white data-[state=checked]:border-mint-600"
            >
                <RadioGroupIndicator class="h-2 w-2 rounded-full bg-mint-600" />
            </RadioGroupItem>
            <label :for="`${id}-${option.value}`" class="cursor-pointer text-sm select-none">
                {{ option.label }}
                <span v-if="option.description" class="block text-xs text-ink-500">{{ option.description }}</span>
            </label>
        </div>
    </RadioGroupRoot>
</template>
