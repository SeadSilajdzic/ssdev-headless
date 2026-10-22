<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

// Another account than the invited one gets nulls.
const props = defineProps<{ team: { name: string } | null; email: string | null; acceptUrl: string | null }>();

const t = useTrans();
const page = usePage();
const accepting = ref(false);
const error = computed(() => page.props.errors.invitation);

function accept(acceptUrl: string): void {
    router.post(acceptUrl, {}, { onStart: () => (accepting.value = true), onFinish: () => (accepting.value = false) });
}
</script>

<template>
    <AppLayout :title="t('teams.invitation.title')">
        <BaseCard class="max-w-xl">
            <div v-if="props.team && props.acceptUrl" class="space-y-4">
                <BaseAlert v-if="error" variant="error">{{ error }}</BaseAlert>
                <p class="text-sm text-ink-700">{{ t('teams.invitation.description', { team: props.team.name, email: props.email ?? '' }) }}</p>
                <BaseButton data-test="accept-invitation" :loading="accepting" @click="accept(props.acceptUrl)">{{ t('teams.invitation.accept') }}</BaseButton>
            </div>
            <BaseAlert v-else data-test="invitation-other-account" variant="error">{{ t('teams.invitation.other_account') }}</BaseAlert>
        </BaseCard>
    </AppLayout>
</template>
