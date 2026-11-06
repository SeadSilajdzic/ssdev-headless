<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Plus, Users } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import UserStatusBadge, { type UserStatus } from '@headless/components/users/UserStatusBadge.vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
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

export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    status: UserStatus;
    email_verified: boolean;
    created_at: string | null;
    blocked_at: string | null;
}

interface UserFilters {
    search: string | null;
    status: UserStatus | null;
    verified: boolean | null;
    role: number | null;
    registered_from: string | null;
    registered_to: string | null;
    sort: string | null;
    direction: 'asc' | 'desc' | null;
}

type QueryValue = string | number | boolean | null;

const props = defineProps<{
    users: { data: ManagedUser[]; meta: { current_page: number; last_page: number; from: number | null; to: number | null; total: number } };
    filters: UserFilters;
    statuses: UserStatus[];
    roles: { id: number; name: string }[];
    can: { create: boolean; update: boolean };
    indexUrl: string;
    createUrl: string;
}>();

const SEARCH_DELAY_MS = 300;
// Select options cannot be empty, so "every value" needs a value of its own.
const ALL = 'all';
const VERIFIED = '1';
const UNVERIFIED = '0';

const t = useTrans();
const search = ref(props.filters.search ?? '');

const columns = computed<TableColumn[]>(() => [
    { key: 'name', label: t('users.columns.name'), sortable: true },
    { key: 'email', label: t('users.columns.email'), sortable: true },
    { key: 'status', label: t('users.columns.status') },
    { key: 'created_at', label: t('users.columns.registered'), sortable: true },
]);

const sort = computed<TableSort | null>(() => (props.filters.sort ? { key: props.filters.sort, direction: props.filters.direction ?? 'asc' } : null));

const statusOptions = computed(() => [{ value: ALL, label: t('users.filters.all') }, ...props.statuses.map((status) => ({ value: status, label: t(`users.statuses.${status}`) }))]);
const verifiedOptions = computed(() => [
    { value: ALL, label: t('users.filters.all') },
    { value: VERIFIED, label: t('users.filters.verified_yes') },
    { value: UNVERIFIED, label: t('users.filters.verified_no') },
]);
const roleOptions = computed(() => [{ value: ALL, label: t('users.filters.all') }, ...props.roles.map((role) => ({ value: String(role.id), label: role.name }))]);

const hasFilters = computed(() => Object.entries(props.filters).some(([key, value]) => key !== 'sort' && key !== 'direction' && value !== null && value !== ''));

function query(changes: Partial<Record<keyof UserFilters | 'page', QueryValue>>): Record<string, string | number> {
    const merged: Record<string, QueryValue> = { ...props.filters, ...changes };
    const entries = Object.entries(merged).filter((entry): entry is [string, string | number | boolean] => entry[1] !== null && entry[1] !== '');

    return Object.fromEntries(entries.map(([key, value]) => [key, typeof value === 'boolean' ? (value ? VERIFIED : UNVERIFIED) : value]));
}

function visit(changes: Partial<Record<keyof UserFilters | 'page', QueryValue>>): void {
    router.get(props.indexUrl, query(changes), { preserveState: true, preserveScroll: true, replace: true });
}

function filterBy(key: keyof UserFilters, value: string | null): void {
    visit({ [key]: value === ALL ? null : value, page: null });
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
    <AppLayout :title="t('users.title')">
        <template v-if="can.create" #actions>
            <BaseButton :href="createUrl" data-test="invite-user">
                <Plus class="h-4 w-4" aria-hidden="true" />
                {{ t('users.invite') }}
            </BaseButton>
        </template>

        <BaseCard :description="t('users.description')">
            <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <FormField v-slot="{ id }" class="sm:col-span-2 lg:col-span-3 xl:col-span-5" :label="t('users.filters.search')">
                    <BaseInput :id="id" v-model="search" type="search" :placeholder="t('users.search')" />
                </FormField>
                <FormField v-slot="{ id }" :label="t('users.filters.status')">
                    <BaseSelect :id="id" :model-value="filters.status ?? ALL" :options="statusOptions" @update:model-value="(value) => filterBy('status', value)" />
                </FormField>
                <FormField v-slot="{ id }" :label="t('users.filters.verified')">
                    <BaseSelect
                        :id="id"
                        :model-value="filters.verified === null ? ALL : filters.verified ? VERIFIED : UNVERIFIED"
                        :options="verifiedOptions"
                        @update:model-value="(value) => filterBy('verified', value)"
                    />
                </FormField>
                <FormField v-if="roles.length" v-slot="{ id }" :label="t('users.filters.role')">
                    <BaseSelect
                        :id="id"
                        :model-value="filters.role === null ? ALL : String(filters.role)"
                        :options="roleOptions"
                        @update:model-value="(value) => filterBy('role', value)"
                    />
                </FormField>
                <FormField v-slot="{ id }" :label="t('users.filters.registered_from')">
                    <BaseInput :id="id" type="date" :model-value="filters.registered_from ?? ''" @update:model-value="(value: string) => filterBy('registered_from', value || null)" />
                </FormField>
                <FormField v-slot="{ id }" :label="t('users.filters.registered_to')">
                    <BaseInput :id="id" type="date" :model-value="filters.registered_to ?? ''" @update:model-value="(value: string) => filterBy('registered_to', value || null)" />
                </FormField>
            </div>
            <div v-if="hasFilters" class="mb-4">
                <BaseButton variant="ghost" size="sm" data-test="clear-filters" @click="clearFilters">{{ t('users.filters.clear') }}</BaseButton>
            </div>

            <BaseTable :columns="columns" :rows="users.data as unknown as Record<string, unknown>[]" :sort="sort" @update:sort="(next) => visit({ sort: next.key, direction: next.direction, page: null })">
                <template #cell-name="{ row }">
                    <Link :href="`${indexUrl}/${row.id}`" class="flex items-center gap-3 font-semibold text-ink-900 hover:text-mint-700">
                        <BaseAvatar :name="String(row.name)" :src="row.avatar_url as string | null" size="sm" />
                        {{ row.name }}
                    </Link>
                </template>
                <template #cell-status="{ row }">
                    <UserStatusBadge :status="row.status as UserStatus" />
                </template>
                <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
                <template #actions="{ row }">
                    <BaseButton :href="`${indexUrl}/${row.id}`" variant="ghost" size="sm" data-test="open-user">
                        {{ can.update ? t('users.actions.edit') : t('users.actions.view') }}
                    </BaseButton>
                </template>
                <template #empty>
                    <EmptyState :icon="Users" :title="t('users.empty.title')" :description="t('users.empty.description')" />
                </template>
            </BaseTable>

            <BasePagination
                v-if="users.meta.last_page > 1"
                class="mt-4"
                :current-page="users.meta.current_page"
                :last-page="users.meta.last_page"
                :from="users.meta.from"
                :to="users.meta.to"
                :total="users.meta.total"
                :url="pageUrl"
            />
        </BaseCard>
    </AppLayout>
</template>
