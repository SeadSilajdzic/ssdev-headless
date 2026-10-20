<script setup lang="ts">
import { ChevronDown, ChevronsUpDown, ChevronUp } from 'lucide-vue-next';
import { useTrans } from '@headless/i18n';

export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
}

export interface TableSort {
    key: string;
    direction: 'asc' | 'desc';
}

const props = withDefaults(
    defineProps<{ columns: TableColumn[]; rows: Record<string, unknown>[]; rowKey?: string; sort?: TableSort | null }>(),
    { rowKey: 'id', sort: null },
);

// Controlled on purpose: the parent usually re-fetches sorted rows from the server.
const emit = defineEmits<{ 'update:sort': [sort: TableSort] }>();
const t = useTrans();

function toggleSort(column: TableColumn): void {
    const direction = props.sort?.key === column.key && props.sort.direction === 'asc' ? 'desc' : 'asc';
    emit('update:sort', { key: column.key, direction });
}

function ariaSort(column: TableColumn): 'ascending' | 'descending' | 'none' | undefined {
    if (!column.sortable) {
        return undefined;
    }

    if (props.sort?.key !== column.key) {
        return 'none';
    }

    return props.sort.direction === 'asc' ? 'ascending' : 'descending';
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :aria-sort="ariaSort(column)"
                        class="border-b border-ink-100 px-4 py-3 text-left text-[13px] font-medium text-ink-500"
                    >
                        <button v-if="column.sortable" type="button" class="flex items-center gap-1 font-medium" @click="toggleSort(column)">
                            {{ column.label }}
                            <ChevronUp v-if="sort?.key === column.key && sort.direction === 'asc'" class="h-3.5 w-3.5" :aria-label="t('common.table.sort_ascending')" />
                            <ChevronDown v-else-if="sort?.key === column.key" class="h-3.5 w-3.5" :aria-label="t('common.table.sort_descending')" />
                            <ChevronsUpDown v-else class="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                        <template v-else>{{ column.label }}</template>
                    </th>
                    <th v-if="$slots.actions" class="border-b border-ink-100 px-4 py-3" />
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in props.rows" :key="String(row[rowKey])" class="[&:last-child>td]:border-0">
                    <td v-for="column in columns" :key="column.key" class="border-b border-ink-100 px-4 py-3.5 text-ink-900">
                        <slot :name="`cell-${column.key}`" :row="row">{{ row[column.key] }}</slot>
                    </td>
                    <td v-if="$slots.actions" class="border-b border-ink-100 px-4 py-3.5 text-right">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
                <tr v-if="props.rows.length === 0">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-10 text-center text-ink-500">
                        <slot name="empty">{{ t('common.table.empty') }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
