<script setup lang="ts">
import { computed } from 'vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import { useTrans } from '@headless/i18n';

export type UserStatus = 'active' | 'invited' | 'blocked';

const props = defineProps<{ status: UserStatus }>();

const COLORS = { active: 'mint', invited: 'blue', blocked: 'rose' } as const;

const t = useTrans();
const color = computed(() => COLORS[props.status]);
</script>

<template>
    <BaseBadge :color="color" dot>{{ t(`users.statuses.${status}`) }}</BaseBadge>
</template>
