<script setup lang="ts">
import ApiTokenList, { type ApiTokenItem } from '@headless/components/settings/ApiTokenList.vue';
import CreateApiTokenForm, { type PermissionGroup } from '@headless/components/settings/CreateApiTokenForm.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';
import SettingsLayout from '@headless/layouts/SettingsLayout.vue';

defineProps<{ tokens: ApiTokenItem[]; permissions: PermissionGroup[]; createdToken: string | null; tokensUrl: string }>();

const t = useTrans();
</script>

<template>
    <SettingsLayout :title="t('settings.api_tokens.title')">
        <BaseCard :title="t('settings.api_tokens.create_title')" :description="t('settings.api_tokens.create_description')">
            <CreateApiTokenForm :permissions="permissions" :store-url="tokensUrl" :created-token="createdToken" />
        </BaseCard>

        <BaseCard :title="t('settings.api_tokens.list_title')" :description="t('settings.api_tokens.list_description')">
            <ApiTokenList :tokens="tokens" :tokens-url="tokensUrl" />
        </BaseCard>
    </SettingsLayout>
</template>
