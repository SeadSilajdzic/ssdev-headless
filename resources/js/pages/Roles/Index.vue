<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Plus, ShieldCheck } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import BasePagination from '@headless/components/ui/BasePagination.vue';
import BaseTable, { type TableColumn, type TableSort } from '@headless/components/ui/BaseTable.vue';
import EmptyState from '@headless/components/ui/EmptyState.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

export interface RoleItem {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
    created_at: string | null;
}

interface RoleFilters {
    search: string | null;
    sort: string | null;
    direction: 'asc' | 'desc' | null;
}

const props = defineProps<{
    roles: { data: RoleItem[]; meta: { current_page: number; last_page: number; from: number | null; to: number | null; total: number } };
    filters: RoleFilters;
    can: { manage: boolean };
    indexUrl: string;
    createUrl: string;
}>();

const SEARCH_DELAY_MS = 300;

const t = useTrans();
const search = ref(props.filters.search ?? '');

const columns = computed<TableColumn[]>(() => [
    { key: 'name', label: t('roles.columns.name'), sortable: true },
    { key: 'permissions_count', label: t('roles.columns.permissions') },
    { key: 'users_count', label: t('roles.columns.members'), sortable: true },
]);

const sort = computed<TableSort | null>(() => (props.filters.sort ? { key: props.filters.sort, direction: props.filters.direction ?? 'asc' } : null));

function query(changes: Partial<Record<keyof RoleFilters | 'page', string | number | null>>): Record<string, string | number> {
    const merged: Record<string, string | number | null> = { ...props.filters, ...changes };

    return Object.fromEntries(Object.entries(merged).filter((entry): entry is [string, string | number] => entry[1] !== null && entry[1] !== ''));
}

function visit(changes: Partial<Record<keyof RoleFilters | 'page', string | number | null>>): void {
    router.get(props.indexUrl, query(changes), { preserveState: true, preserveScroll: true, replace: true });
}

function pageUrl(page: number): string {
    return `${props.indexUrl}?${new URLSearchParams(Object.entries(query({ page })).map(([key, value]) => [key, String(value)]))}`;
}

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => visit({ search: value, page: null }), SEARCH_DELAY_MS);
});
</script>

<template>
    <AppLayout :title="t('roles.title')">
        <template v-if="can.manage" #actions>
            <BaseButton :href="createUrl">
                <Plus class="h-4 w-4" aria-hidden="true" />
                {{ t('roles.new') }}
            </BaseButton>
        </template>

        <BaseCard :description="t('roles.description')">
            <div class="mb-4 max-w-sm">
                <BaseInput v-model="search" type="search" :placeholder="t('roles.search')" :aria-label="t('roles.search')" />
            </div>

            <BaseTable :columns="columns" :rows="roles.data as unknown as Record<string, unknown>[]" :sort="sort" @update:sort="(next) => visit({ sort: next.key, direction: next.direction, page: null })">
                <template #cell-name="{ row }">
                    <Link :href="`${indexUrl}/${row.id}`" class="font-semibold text-ink-900 hover:text-mint-700" data-test="role-link">{{ row.name }}</Link>
                </template>
                <template #empty>
                    <EmptyState :icon="ShieldCheck" :title="t('roles.empty.title')" :description="t('roles.empty.description')" />
                </template>
            </BaseTable>

            <BasePagination
                v-if="roles.meta.last_page > 1"
                class="mt-4"
                :current-page="roles.meta.current_page"
                :last-page="roles.meta.last_page"
                :from="roles.meta.from"
                :to="roles.meta.to"
                :total="roles.meta.total"
                :url="pageUrl"
            />
        </BaseCard>
    </AppLayout>
</template>
