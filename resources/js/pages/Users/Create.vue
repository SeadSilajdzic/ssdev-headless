<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const props = defineProps<{ indexUrl: string; storeUrl: string }>();

const t = useTrans();
const form = useForm({ name: '', email: '' });

function submit(): void {
    form.post(props.storeUrl);
}
</script>

<template>
    <AppLayout :title="t('users.create.title')" :breadcrumbs="[{ label: t('users.title'), href: indexUrl }, { label: t('users.create.title') }]">
        <BaseCard :description="t('users.create.description')">
            <form class="max-w-xl space-y-5" novalidate @submit.prevent="submit">
                <FormField v-slot="{ id, describedBy, invalid }" :label="t('users.fields.name')" :error="form.errors.name" required>
                    <BaseInput :id="id" v-model="form.name" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>

                <FormField v-slot="{ id, describedBy, invalid }" :label="t('users.fields.email')" :error="form.errors.email" required>
                    <BaseInput :id="id" v-model="form.email" type="email" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>

                <BaseButton type="submit" :loading="form.processing">{{ t('users.create.submit') }}</BaseButton>
            </form>
        </BaseCard>
    </AppLayout>
</template>
