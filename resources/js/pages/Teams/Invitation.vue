<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const props = defineProps<{ team: { name: string }; email: string; acceptUrl: string }>();

const t = useTrans();
const page = usePage();
const accepting = ref(false);
const error = computed(() => page.props.errors.invitation);

function accept(): void {
    router.post(props.acceptUrl, {}, { onStart: () => (accepting.value = true), onFinish: () => (accepting.value = false) });
}
</script>

<template>
    <AppLayout :title="t('teams.invitation.title')">
        <BaseCard class="max-w-xl">
            <div class="space-y-4">
                <BaseAlert v-if="error" variant="error">{{ error }}</BaseAlert>
                <p class="text-sm text-ink-700">{{ t('teams.invitation.description', { team: team.name, email }) }}</p>
                <BaseButton data-test="accept-invitation" :loading="accepting" @click="accept">{{ t('teams.invitation.accept') }}</BaseButton>
            </div>
        </BaseCard>
    </AppLayout>
</template>
