<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Monitor, Smartphone } from '@lucide/vue';
import { ref } from 'vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseModal from '@headless/components/ui/BaseModal.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useTrans } from '@headless/i18n';

export interface BrowserSessionItem {
    id: string;
    platform: string | null;
    browser: string | null;
    ip_address: string | null;
    last_active: string;
    is_current: boolean;
    logout_url: string;
}

const props = defineProps<{ supported: boolean; sessions: BrowserSessionItem[]; logoutOthersUrl: string }>();

const MOBILE_PLATFORMS = ['iOS', 'Android'];

const t = useTrans();
const confirming = ref(false);
const loggingOut = ref<string | null>(null);
const form = useForm({ password: '' });

function describe(session: BrowserSessionItem): string {
    const parts = [session.platform, session.browser].filter(Boolean);

    return parts.length ? parts.join(' · ') : t('settings.sessions.unknown');
}

function logout(session: BrowserSessionItem): void {
    router.delete(session.logout_url, {
        preserveScroll: true,
        onStart: () => (loggingOut.value = session.id),
        onFinish: () => (loggingOut.value = null),
    });
}

function logoutOthers(): void {
    form.delete(props.logoutOthersUrl, {
        preserveScroll: true,
        onSuccess: () => (confirming.value = false),
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <BaseAlert v-if="!supported" variant="warning" data-test="sessions-unsupported">{{ t('settings.sessions.unsupported') }}</BaseAlert>

    <div v-else class="space-y-6">
        <ul class="divide-y divide-ink-100">
            <li v-for="session in sessions" :key="session.id" class="flex items-center gap-4 py-3">
                <component
                    :is="MOBILE_PLATFORMS.includes(session.platform ?? '') ? Smartphone : Monitor"
                    class="h-8 w-8 shrink-0 text-ink-400"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-ink-900">
                        {{ describe(session) }}
                        <BaseBadge v-if="session.is_current" class="ml-2">{{ t('settings.sessions.this_device') }}</BaseBadge>
                    </p>
                    <p class="text-xs text-ink-500">{{ [session.ip_address, session.last_active].filter(Boolean).join(' · ') }}</p>
                </div>
                <BaseButton
                    v-if="!session.is_current"
                    variant="ghost"
                    size="sm"
                    data-test="logout-session"
                    :loading="loggingOut === session.id"
                    @click="logout(session)"
                >
                    {{ t('settings.sessions.log_out') }}
                </BaseButton>
            </li>
        </ul>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-4">
            <p class="text-sm text-ink-500">{{ t('settings.sessions.log_out_others_hint') }}</p>
            <BaseButton variant="ghost" @click="confirming = true">{{ t('settings.sessions.log_out_others') }}</BaseButton>
        </div>

        <BaseModal v-model:open="confirming" :title="t('settings.sessions.confirm_title')" :description="t('settings.sessions.confirm_description')">
            <form id="logout-other-sessions" novalidate @submit.prevent="logoutOthers">
                <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                    <PasswordInput :id="id" v-model="form.password" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>
            </form>

            <template #footer>
                <BaseButton variant="ghost" @click="confirming = false">{{ t('common.actions.cancel') }}</BaseButton>
                <BaseButton type="submit" form="logout-other-sessions" :loading="form.processing">{{ t('settings.sessions.log_out_others') }}</BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
