<script setup lang="ts">
import BrowserSessions, { type BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';
import TwoFactorSummary from '@headless/components/two-factor/TwoFactorSummary.vue';
import type { TwoFactorStatus } from '@headless/components/two-factor/TwoFactorStatusBadge.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';
import SettingsLayout from '@headless/layouts/SettingsLayout.vue';

defineProps<{
    sessionsSupported: boolean;
    sessions: BrowserSessionItem[];
    logoutOthersUrl: string;
    twoFactor?: { status: TwoFactorStatus; manageUrl: string };
}>();

const t = useTrans();
</script>

<template>
    <SettingsLayout :title="t('settings.security.title')">
        <TwoFactorSummary v-if="twoFactor" :status="twoFactor.status" :manage-url="twoFactor.manageUrl" />

        <BaseCard :title="t('settings.sessions.title')" :description="t('settings.sessions.description')">
            <BrowserSessions :supported="sessionsSupported" :sessions="sessions" :logout-others-url="logoutOthersUrl" />
        </BaseCard>
    </SettingsLayout>
</template>
