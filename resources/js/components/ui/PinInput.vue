<script setup lang="ts">
import { PinInputInput, PinInputRoot } from 'reka-ui';
import { computed } from 'vue';
import { inputClasses } from '@headless/components/ui/inputClasses';

const props = withDefaults(defineProps<{ length?: number; invalid?: boolean }>(), { length: 6 });

const emit = defineEmits<{ complete: [code: string] }>();

const model = defineModel<string>({ default: '' });

const digits = computed({
    get: () => model.value.split('').map(Number),
    set: (value: number[]) => {
        model.value = value.join('');
    },
});
</script>

<template>
    <PinInputRoot v-model="digits" type="number" otp class="flex justify-between gap-2" @complete="emit('complete', $event.join(''))">
        <PinInputInput
            v-for="(_, index) in props.length"
            :key="index"
            :index="index"
            :aria-invalid="invalid ? 'true' : undefined"
            :class="[inputClasses(invalid), 'h-14 text-center font-display text-2xl font-bold']"
        />
    </PinInputRoot>
</template>
