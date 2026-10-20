<script setup lang="ts">
defineProps<{ label?: string }>();
</script>

<template>
    <span role="status" class="inline-flex items-center">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.3" stroke-width="3" />
            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
        <span v-if="label" class="sr-only">{{ label }}</span>
    </span>
</template>
