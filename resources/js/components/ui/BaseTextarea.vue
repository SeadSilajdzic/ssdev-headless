<script setup lang="ts">
import { inputClasses } from '@headless/components/ui/inputClasses';

withDefaults(defineProps<{ invalid?: boolean; rows?: number; placeholder?: string }>(), { rows: 3 });

const model = defineModel<string>({ default: '' });
</script>

<template>
    <textarea
        v-model="model"
        :rows="rows"
        :placeholder="placeholder"
        :aria-invalid="invalid ? 'true' : undefined"
        :class="[inputClasses(invalid), 'min-h-[80px]']"
    />
</template>
