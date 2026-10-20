<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { resolveNavigationIcon } from '@headless/icons';
import type { NavigationSection } from '@headless/types';

defineProps<{ sections: NavigationSection[] }>();

const MAIN_GROUP = 'main';
</script>

<template>
    <div v-for="section in sections" :key="section.group">
        <div v-if="section.group !== MAIN_GROUP" class="px-4 pt-4 pb-2 text-[11px] font-semibold tracking-wider text-ink-400 uppercase">
            {{ section.label }}
        </div>
        <div class="mt-1 space-y-0.5">
            <Link
                v-for="item in section.items"
                :key="item.key"
                :href="item.url"
                :aria-current="item.active ? 'page' : undefined"
                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors"
                :class="item.active ? 'bg-mint-900 text-white' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900'"
            >
                <component :is="resolveNavigationIcon(item.icon)" class="h-4 w-4" aria-hidden="true" />
                <span>{{ item.label }}</span>
            </Link>
        </div>
    </div>
</template>
