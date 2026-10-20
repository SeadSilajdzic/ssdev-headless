<script setup lang="ts">
import { SwitchRoot, SwitchThumb } from 'reka-ui';
import { useId } from 'vue';

defineProps<{ label: string }>();

const model = defineModel<boolean>({ default: false });
const id = useId();
</script>

<template>
    <div class="flex items-center gap-3">
        <SwitchRoot
            :id="id"
            v-model="model"
            class="relative h-6 w-11 shrink-0 rounded-full bg-ink-200 transition-colors data-[state=checked]:bg-mint-500"
        >
            <SwitchThumb class="block h-5 w-5 translate-x-0.5 rounded-full bg-white transition-transform data-[state=checked]:translate-x-[22px]" />
        </SwitchRoot>
        <label :for="id" class="cursor-pointer text-sm select-none">{{ label }}</label>
    </div>
</template>
