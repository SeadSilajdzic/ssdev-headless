<script setup lang="ts">
import AvatarForm from '@headless/components/settings/AvatarForm.vue';
import ProfileInformationForm from '@headless/components/settings/ProfileInformationForm.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';
import SettingsLayout from '@headless/layouts/SettingsLayout.vue';

defineProps<{
    mustVerifyEmail: boolean;
    avatar: { accept: string[]; maxKilobytes: number };
    updateUrl: string;
    avatarUrl: string;
    verificationUrl: string;
}>();

const t = useTrans();
</script>

<template>
    <SettingsLayout :title="t('settings.profile.title')">
        <BaseCard :title="t('settings.profile.information_title')" :description="t('settings.profile.information_description')">
            <div class="space-y-8">
                <AvatarForm :upload-url="avatarUrl" :accept="avatar.accept" :max-kilobytes="avatar.maxKilobytes" />
                <ProfileInformationForm :must-verify-email="mustVerifyEmail" :update-url="updateUrl" :verification-url="verificationUrl" />
            </div>
        </BaseCard>
    </SettingsLayout>
</template>
