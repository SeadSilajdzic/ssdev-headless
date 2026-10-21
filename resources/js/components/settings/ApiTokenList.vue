<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import EmptyState from '@headless/components/ui/EmptyState.vue';
import { useTrans } from '@headless/i18n';

export interface ApiTokenItem {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string | null;
}

const props = defineProps<{ tokens: ApiTokenItem[]; tokensUrl: string }>();

const t = useTrans();
const revoking = ref<ApiTokenItem | null>(null);
const busy = ref(false);

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function revoke(): void {
    if (!revoking.value) {
        return;
    }

    router.delete(`${props.tokensUrl}/${revoking.value.id}`, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => {
            busy.value = false;
            revoking.value = null;
        },
    });
}
</script>

<template>
    <EmptyState v-if="!tokens.length" :title="t('settings.api_tokens.empty')" />

    <ul v-else class="divide-y divide-ink-100">
        <li v-for="token in tokens" :key="token.id" class="flex flex-wrap items-center gap-4 py-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink-900">{{ token.name }}</p>
                <p class="text-xs text-ink-500">
                    {{ token.last_used_at ? t('settings.api_tokens.last_used', { date: formatDate(token.last_used_at) }) : t('settings.api_tokens.never_used') }}
                    <template v-if="token.expires_at"> · {{ t('settings.api_tokens.expires', { date: formatDate(token.expires_at) }) }}</template>
                </p>
                <div v-if="token.abilities.length" class="mt-1 flex flex-wrap gap-1">
                    <BaseBadge v-for="ability in token.abilities" :key="ability" color="ink">{{ ability }}</BaseBadge>
                </div>
            </div>
            <BaseButton variant="ghost" size="sm" data-test="revoke-token" @click="revoking = token">{{ t('settings.api_tokens.revoke') }}</BaseButton>
        </li>
    </ul>

    <ConfirmDialog
        :open="revoking !== null"
        :title="t('settings.api_tokens.revoke_title')"
        :description="t('settings.api_tokens.revoke_description')"
        :confirm-label="t('settings.api_tokens.revoke')"
        destructive
        :loading="busy"
        @update:open="(open: boolean) => !open && (revoking = null)"
        @confirm="revoke"
    />
</template>
