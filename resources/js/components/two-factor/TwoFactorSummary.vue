<script setup lang="ts">
import TwoFactorStatusBadge, { type TwoFactorStatus } from '@headless/components/two-factor/TwoFactorStatusBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';

defineProps<{ status: TwoFactorStatus; manageUrl: string }>();

const t = useTrans();
</script>

<template>
    <BaseCard :title="t('two_factor.title')" :description="t('two_factor.description')">
        <template #actions>
            <TwoFactorStatusBadge :status="status" />
        </template>
        <BaseButton :href="manageUrl" :variant="status === 'enabled' ? 'ghost' : 'primary'">
            {{ status === 'enabled' ? t('two_factor.manage') : t('two_factor.set_up') }}
        </BaseButton>
    </BaseCard>
</template>
