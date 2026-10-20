<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import type { Component } from 'vue';

withDefaults(defineProps<{ title: string; description?: string; icon?: Component }>(), { icon: () => Inbox });
</script>

<template>
    <div class="rounded-xl border border-dashed border-ink-200 py-10 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-mint-100">
            <component :is="icon" class="h-7 w-7 text-mint-700" aria-hidden="true" />
        </div>
        <div class="mt-4 font-semibold">{{ title }}</div>
        <p v-if="description" class="mx-auto mt-1 max-w-xs text-sm text-ink-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
