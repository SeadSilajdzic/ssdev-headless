<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PinInput from '@headless/components/ui/PinInput.vue';
import { useTrans } from '@headless/i18n';
import AuthLayout from '@headless/layouts/AuthLayout.vue';

const props = defineProps<{ submitUrl: string; loginUrl: string }>();

const t = useTrans();
const usingRecoveryCode = ref(false);
const form = useForm({ code: '', recovery_code: '' });

const title = computed(() => (usingRecoveryCode.value ? t('two_factor.challenge.recovery_title') : t('two_factor.challenge.code_title')));
const subtitle = computed(() =>
    usingRecoveryCode.value ? t('two_factor.challenge.recovery_description') : t('two_factor.challenge.code_description'),
);

function switchMode(): void {
    usingRecoveryCode.value = !usingRecoveryCode.value;
    form.reset();
    form.clearErrors();
}

function submit(): void {
    form.transform((data) => (usingRecoveryCode.value ? { recovery_code: data.recovery_code } : { code: data.code })).post(props.submitUrl, {
        onError: () => form.reset(),
    });
}
</script>

<template>
    <AuthLayout :title="title" :subtitle="subtitle">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField
                v-if="usingRecoveryCode"
                v-slot="{ id, describedBy, invalid }"
                :label="t('two_factor.challenge.recovery_code')"
                :error="form.errors.recovery_code"
                required
            >
                <BaseInput
                    :id="id"
                    v-model="form.recovery_code"
                    class="font-mono"
                    autocomplete="one-time-code"
                    placeholder="XXXXXXXXXX-XXXXXXXXXX"
                    :invalid="invalid"
                    :aria-describedby="describedBy"
                />
            </FormField>

            <FormField v-else v-slot="{ invalid }" :label="t('two_factor.challenge.code_title')" :error="form.errors.code" required>
                <PinInput v-model="form.code" :invalid="invalid" @complete="submit" />
            </FormField>

            <BaseButton type="submit" size="lg" class="w-full" :loading="form.processing">{{ t('two_factor.challenge.submit') }}</BaseButton>
        </form>

        <button type="button" class="mt-4 w-full text-center text-sm font-semibold text-mint-700 hover:underline" data-test="switch-mode" @click="switchMode">
            {{ usingRecoveryCode ? t('two_factor.challenge.use_code') : t('two_factor.challenge.use_recovery_code') }}
        </button>

        <p class="mt-8 text-center text-sm text-ink-500">
            <Link :href="loginUrl" class="font-semibold text-mint-700 hover:text-mint-800">{{ t('two_factor.challenge.back_to_login') }}</Link>
        </p>
    </AuthLayout>
</template>
