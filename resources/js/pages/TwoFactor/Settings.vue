<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RecoveryCodes from '@headless/components/two-factor/RecoveryCodes.vue';
import TwoFactorSetupForm, { type TwoFactorSetup } from '@headless/components/two-factor/TwoFactorSetupForm.vue';
import TwoFactorStatusBadge, { type TwoFactorStatus } from '@headless/components/two-factor/TwoFactorStatusBadge.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import { useTrans } from '@headless/i18n';
import SettingsLayout from '@headless/layouts/SettingsLayout.vue';

const props = defineProps<{
    status: TwoFactorStatus;
    setup: TwoFactorSetup | null;
    recoveryCodes: string[] | null;
    enableUrl: string;
    confirmUrl: string;
    recoveryCodesUrl: string;
    disableUrl: string;
}>();

const t = useTrans();
const page = usePage();
const processing = ref(false);
const confirmingDisable = ref(false);

const error = computed(() => page.props.errors.two_factor);

function post(url: string): void {
    router.post(url, {}, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });
}

function disable(): void {
    router.delete(props.disableUrl, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => {
            processing.value = false;
            confirmingDisable.value = false;
        },
    });
}
</script>

<template>
    <SettingsLayout :title="t('two_factor.title')">
        <BaseCard :title="t('two_factor.title')" :description="t('two_factor.description')">
            <template #actions>
                <TwoFactorStatusBadge :status="status" />
            </template>

            <div class="space-y-6">
                <BaseAlert v-if="error" variant="error">{{ error }}</BaseAlert>

                <BaseButton v-if="status === 'disabled'" :loading="processing" data-test="enable-two-factor" @click="post(enableUrl)">
                    {{ t('two_factor.enable') }}
                </BaseButton>

                <TwoFactorSetupForm v-if="status === 'pending' && setup" :setup="setup" :confirm-url="confirmUrl" :disable-url="disableUrl" />

                <template v-if="status === 'enabled'">
                    <RecoveryCodes v-if="recoveryCodes" :codes="recoveryCodes" />

                    <div class="flex flex-wrap gap-2">
                        <BaseButton variant="ghost" :loading="processing" @click="post(recoveryCodesUrl)">
                            {{ t('two_factor.recovery_codes.regenerate') }}
                        </BaseButton>
                        <BaseButton variant="destructive" @click="confirmingDisable = true">{{ t('two_factor.disable.button') }}</BaseButton>
                    </div>
                </template>
            </div>
        </BaseCard>

        <ConfirmDialog
            v-model:open="confirmingDisable"
            :title="t('two_factor.disable.confirm_title')"
            :description="t('two_factor.disable.confirm_description')"
            :confirm-label="t('two_factor.disable.button')"
            :loading="processing"
            destructive
            @confirm="disable"
        />
    </SettingsLayout>
</template>
