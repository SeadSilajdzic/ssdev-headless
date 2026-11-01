<script setup lang="ts">
import { computed } from 'vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import { useTrans } from '@headless/i18n';

export type TenantStatus = 'active' | 'suspended';

const props = defineProps<{ status: TenantStatus }>();

const COLORS = { active: 'mint', suspended: 'amber' } as const;

const t = useTrans();
const color = computed(() => COLORS[props.status]);
</script>

<template>
    <BaseBadge :color="color" dot>{{ t(`tenancy.statuses.${status}`) }}</BaseBadge>
</template>
