<script setup lang="ts">
import { ProgressIndicator, ProgressRoot } from 'reka-ui';

defineProps<{ value: number; label: string }>();
</script>

<template>
    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <span class="text-sm font-medium">{{ label }}</span>
            <span class="text-xs text-ink-500">{{ value }}%</span>
        </div>
        <ProgressRoot :model-value="value" :aria-label="label" class="h-2 w-full overflow-hidden rounded-full bg-ink-100">
            <ProgressIndicator class="h-full rounded-full bg-mint-500 transition-[width]" :style="{ width: `${value}%` }" />
        </ProgressRoot>
    </div>
</template>
