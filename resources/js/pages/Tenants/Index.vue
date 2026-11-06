<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Building2, Plus } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import TenantStatusBadge, { type TenantStatus } from '@headless/components/tenancy/TenantStatusBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import BasePagination from '@headless/components/ui/BasePagination.vue';
import BaseSelect from '@headless/components/ui/BaseSelect.vue';
import BaseTable, { type TableColumn, type TableSort } from '@headless/components/ui/BaseTable.vue';
import EmptyState from '@headless/components/ui/EmptyState.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

export interface TenantItem {
    id: number;
    name: string;
    subdomain: string;
    url: string;
    status: TenantStatus;
    suspended_at: string | null;
    created_at: string | null;
    owner: { email: string; accepted: boolean } | null;
}

interface TenantFilters {
    search: string | null;
    status: TenantStatus | null;
    sort: string | null;
    direction: 'asc' | 'desc' | null;
}

type QueryValue = string | number | null;

const props = defineProps<{
    tenants: { data: TenantItem[]; meta: { current_page: number; last_page: number; from: number | null; to: number | null; total: number } };
    filters: TenantFilters;
    statuses: TenantStatus[];
    can: { manage: boolean };
    indexUrl: string;
    createUrl: string;
}>();

const SEARCH_DELAY_MS = 300;
// Select options cannot be empty, so "every value" needs a value of its own.
const ALL = 'all';

const t = useTrans();
const search = ref(props.filters.search ?? '');

const columns = computed<TableColumn[]>(() => [
    { key: 'name', label: t('tenancy.columns.name'), sortable: true },
    { key: 'subdomain', label: t('tenancy.columns.subdomain'), sortable: true },
    { key: 'owner', label: t('tenancy.columns.owner') },
    { key: 'status', label: t('tenancy.columns.status') },
    { key: 'created_at', label: t('tenancy.columns.created'), sortable: true },
]);

const sort = computed<TableSort | null>(() => (props.filters.sort ? { key: props.filters.sort, direction: props.filters.direction ?? 'asc' } : null));

const statusOptions = computed(() => [{ value: ALL, label: t('tenancy.filters.all') }, ...props.statuses.map((status) => ({ value: status, label: t(`tenancy.statuses.${status}`) }))]);

const hasFilters = computed(() => Boolean(props.filters.search) || props.filters.status !== null);

function query(changes: Partial<Record<keyof TenantFilters | 'page', QueryValue>>): Record<string, string | number> {
    const merged: Record<string, QueryValue> = { ...props.filters, ...changes };

    return Object.fromEntries(Object.entries(merged).filter((entry): entry is [string, string | number] => entry[1] !== null && entry[1] !== ''));
}

function visit(changes: Partial<Record<keyof TenantFilters | 'page', QueryValue>>): void {
    router.get(props.indexUrl, query(changes), { preserveState: true, preserveScroll: true, replace: true });
}

function clearFilters(): void {
    search.value = '';
    router.get(props.indexUrl, {}, { preserveScroll: true, replace: true });
}

function pageUrl(page: number): string {
    return `${props.indexUrl}?${new URLSearchParams(Object.entries(query({ page })).map(([key, value]) => [key, String(value)]))}`;
}

function formatDate(value: unknown): string {
    return typeof value === 'string' ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
}

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => visit({ search: value, page: null }), SEARCH_DELAY_MS);
});

onBeforeUnmount(() => clearTimeout(searchTimer));
</script>

<template>
    <AppLayout :title="t('tenancy.title')">
        <template v-if="can.manage" #actions>
            <BaseButton :href="createUrl" data-test="new-tenant">
                <Plus class="h-4 w-4" aria-hidden="true" />
                {{ t('tenancy.new') }}
            </BaseButton>
        </template>

        <BaseCard :description="t('tenancy.description')">
            <div class="mb-4 grid gap-3 sm:grid-cols-3">
                <FormField v-slot="{ id }" class="sm:col-span-2" :label="t('tenancy.filters.search')">
                    <BaseInput :id="id" v-model="search" type="search" :placeholder="t('tenancy.search')" />
                </FormField>
                <FormField v-slot="{ id }" :label="t('tenancy.filters.status')">
                    <BaseSelect
                        :id="id"
                        :model-value="filters.status ?? ALL"
                        :options="statusOptions"
                        @update:model-value="(value) => visit({ status: value === ALL ? null : value, page: null })"
                    />
                </FormField>
            </div>
            <div v-if="hasFilters" class="mb-4">
                <BaseButton variant="ghost" size="sm" data-test="clear-filters" @click="clearFilters">{{ t('tenancy.filters.clear') }}</BaseButton>
            </div>

            <BaseTable :columns="columns" :rows="tenants.data as unknown as Record<string, unknown>[]" :sort="sort" @update:sort="(next) => visit({ sort: next.key, direction: next.direction, page: null })">
                <template #cell-name="{ row }">
                    <Link :href="`${indexUrl}/${row.id}`" class="font-semibold text-ink-900 hover:text-mint-700">{{ row.name }}</Link>
                </template>
                <template #cell-owner="{ row }">{{ (row.owner as TenantItem['owner'])?.email ?? '' }}</template>
                <template #cell-status="{ row }">
                    <TenantStatusBadge :status="row.status as TenantStatus" />
                </template>
                <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
                <template #actions="{ row }">
                    <BaseButton :href="`${indexUrl}/${row.id}`" variant="ghost" size="sm" data-test="open-tenant">
                        {{ can.manage ? t('tenancy.actions.edit') : t('tenancy.actions.view') }}
                    </BaseButton>
                </template>
                <template #empty>
                    <EmptyState :icon="Building2" :title="t('tenancy.empty.title')" :description="t('tenancy.empty.description')" />
                </template>
            </BaseTable>

            <BasePagination
                v-if="tenants.meta.last_page > 1"
                class="mt-4"
                :current-page="tenants.meta.current_page"
                :last-page="tenants.meta.last_page"
                :from="tenants.meta.from"
                :to="tenants.meta.to"
                :total="tenants.meta.total"
                :url="pageUrl"
            />
        </BaseCard>
    </AppLayout>
</template>
