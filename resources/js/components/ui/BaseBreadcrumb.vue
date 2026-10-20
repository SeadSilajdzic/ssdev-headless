<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, LayoutGrid } from '@lucide/vue';
import { useTrans } from '@headless/i18n';

defineProps<{ items: { label: string; href?: string }[] }>();

const t = useTrans();
</script>

<template>
    <nav :aria-label="t('common.layout.breadcrumb')">
        <ol class="flex items-center gap-2 text-sm text-ink-500">
            <li aria-hidden="true"><LayoutGrid class="h-4 w-4" /></li>
            <li v-for="(item, index) in items" :key="index" class="flex items-center gap-2">
                <ChevronRight v-if="index > 0" class="h-3 w-3" aria-hidden="true" />
                <Link v-if="item.href && index < items.length - 1" :href="item.href" class="hover:text-ink-900">{{ item.label }}</Link>
                <span v-else :aria-current="index === items.length - 1 ? 'page' : undefined" class="font-medium text-ink-900">{{ item.label }}</span>
            </li>
        </ol>
    </nav>
</template>
