<script setup lang="ts">
withDefaults(defineProps<{ label: string; type?: 'button' | 'submit' }>(), { type: 'button' });
</script>

<template>
    <button :type="type" :aria-label="label" class="grid h-10 w-10 place-items-center rounded-full text-ink-600 transition hover:bg-ink-100">
        <slot />
    </button>
</template>
