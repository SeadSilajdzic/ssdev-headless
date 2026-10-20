<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{
    currentPage: number;
    lastPage: number;
    from: number | null;
    to: number | null;
    total: number;
    url: (page: number) => string;
}>();

const MAX_UNCOLLAPSED_PAGES = 7;

const t = useTrans();

const pages = computed<(number | null)[]>(() => {
    const all = Array.from({ length: props.lastPage }, (_, index) => index + 1);

    if (props.lastPage <= MAX_UNCOLLAPSED_PAGES) {
        return all;
    }

    const visible = all.filter((page) => page === 1 || page === props.lastPage || Math.abs(page - props.currentPage) <= 1);

    return visible.flatMap((page, index) => (index > 0 && page - visible[index - 1] > 1 ? [null, page] : [page]));
});
</script>

<template>
    <nav class="flex items-center justify-between" :aria-label="t('common.pagination.next')">
        <div class="text-sm text-ink-500">
            {{ t('common.pagination.showing', { from: from ?? 0, to: to ?? 0, total }) }}
        </div>
        <div class="flex items-center gap-1">
            <Link
                v-if="currentPage > 1"
                :href="url(currentPage - 1)"
                class="grid h-8 w-8 place-items-center rounded-md text-ink-500 hover:bg-ink-100"
                :aria-label="t('common.pagination.previous')"
            >
                <ChevronLeft class="h-4 w-4" aria-hidden="true" />
            </Link>
            <span v-else class="grid h-8 w-8 place-items-center rounded-md text-ink-300" aria-hidden="true">
                <ChevronLeft class="h-4 w-4" />
            </span>

            <template v-for="(page, index) in pages" :key="page ?? `gap-${index}`">
                <span v-if="page === null" class="px-1 text-ink-400" aria-hidden="true">…</span>
                <Link
                    v-else
                    :href="url(page)"
                    :data-page="page"
                    :aria-current="page === currentPage ? 'page' : undefined"
                    class="grid h-8 w-8 place-items-center rounded-md text-sm"
                    :class="page === currentPage ? 'bg-mint-900 font-semibold text-white' : 'hover:bg-ink-100'"
                >
                    {{ page }}
                </Link>
            </template>

            <Link
                v-if="currentPage < lastPage"
                :href="url(currentPage + 1)"
                class="grid h-8 w-8 place-items-center rounded-md text-ink-500 hover:bg-ink-100"
                :aria-label="t('common.pagination.next')"
            >
                <ChevronRight class="h-4 w-4" aria-hidden="true" />
            </Link>
            <span v-else class="grid h-8 w-8 place-items-center rounded-md text-ink-300" aria-hidden="true">
                <ChevronRight class="h-4 w-4" />
            </span>
        </div>
    </nav>
</template>
