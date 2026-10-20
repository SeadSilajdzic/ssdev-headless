<script setup lang="ts">
import { inputClasses } from '@headless/components/ui/inputClasses';

withDefaults(defineProps<{ type?: string; invalid?: boolean; placeholder?: string; autocomplete?: string }>(), { type: 'text' });

// Attributes from FormField (id, aria-*) belong on the input, not on the wrapper.
defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="relative">
        <span v-if="$slots.prefix" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-400">
            <slot name="prefix" />
        </span>
        <input
            v-bind="$attrs"
            v-model="model"
            :type="type"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :aria-invalid="invalid ? 'true' : undefined"
            :class="[inputClasses(invalid), { 'pl-11': $slots.prefix, 'pr-11': $slots.suffix }]"
        />
        <span v-if="$slots.suffix" class="absolute top-1/2 right-3 -translate-y-1/2 text-ink-400">
            <slot name="suffix" />
        </span>
    </div>
</template>
