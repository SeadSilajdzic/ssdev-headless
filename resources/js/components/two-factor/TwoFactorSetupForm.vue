<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PinInput from '@headless/components/ui/PinInput.vue';
import { useTrans } from '@headless/i18n';

export interface TwoFactorSetup {
    secret: string;
    otpauth_url: string;
    qr_code: string;
}

const props = defineProps<{ setup: TwoFactorSetup; confirmUrl: string; disableUrl: string }>();

const t = useTrans();
const form = useForm({ code: '' });

function submit(): void {
    form.post(props.confirmUrl, { preserveScroll: true, onError: () => form.reset() });
}

function cancel(): void {
    router.delete(props.disableUrl, { preserveScroll: true });
}
</script>

<template>
    <form class="grid gap-6 sm:grid-cols-[auto_1fr]" novalidate @submit.prevent="submit">
        <div class="grid place-items-center self-start rounded-xl border border-ink-200 bg-white p-4">
            <img :src="setup.qr_code" :alt="t('two_factor.setup.qr_alt')" class="h-40 w-40" />
        </div>

        <div class="min-w-0 space-y-4">
            <p class="text-sm text-ink-600">{{ t('two_factor.setup.scan') }}</p>

            <div>
                <p class="mb-1 text-xs font-semibold tracking-wider text-ink-400 uppercase">{{ t('two_factor.setup.key') }}</p>
                <code class="block rounded-lg bg-ink-50 px-3 py-2 font-mono text-sm break-all text-ink-900" data-test="setup-key">{{ setup.secret }}</code>
            </div>

            <FormField v-slot="{ invalid }" :label="t('two_factor.setup.code')" :error="form.errors.code" required>
                <PinInput v-model="form.code" :invalid="invalid" @complete="submit" />
            </FormField>

            <div class="flex flex-wrap justify-end gap-2">
                <BaseButton variant="ghost" @click="cancel">{{ t('two_factor.setup.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="form.processing">{{ t('two_factor.setup.confirm') }}</BaseButton>
            </div>
        </div>
    </form>
</template>
