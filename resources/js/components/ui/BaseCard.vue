<script setup lang="ts">
withDefaults(defineProps<{ title?: string; description?: string; padding?: 'md' | 'lg' }>(), { padding: 'md' });
</script>

<template>
    <section class="rounded-card border border-ink-100 bg-white shadow-card" :class="padding === 'lg' ? 'p-8' : 'p-6'">
        <header v-if="title || $slots.actions" class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h3 v-if="title" class="font-display text-xl font-bold">{{ title }}</h3>
                <p v-if="description" class="mt-1 max-w-lg text-sm text-ink-500">{{ description }}</p>
            </div>
            <slot name="actions" />
        </header>
        <slot />
    </section>
</template>
