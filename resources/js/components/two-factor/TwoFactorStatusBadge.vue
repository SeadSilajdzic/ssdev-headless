<script setup lang="ts">
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import { useTrans } from '@headless/i18n';

export type TwoFactorStatus = 'disabled' | 'pending' | 'enabled';

defineProps<{ status: TwoFactorStatus }>();

const COLORS = { disabled: 'ink', pending: 'amber', enabled: 'mint' } as const;

const t = useTrans();
</script>

<template>
    <BaseBadge :color="COLORS[status]" data-test="two-factor-status">{{ t(`two_factor.status.${status}`) }}</BaseBadge>
</template>
