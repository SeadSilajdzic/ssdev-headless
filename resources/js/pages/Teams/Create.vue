<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const props = defineProps<{ storeUrl: string }>();

const t = useTrans();
const form = useForm({ name: '' });

function submit(): void {
    form.post(props.storeUrl);
}
</script>

<template>
    <AppLayout :title="t('teams.create.title')">
        <BaseCard :description="t('teams.create.description')">
            <form class="max-w-xl space-y-5" novalidate @submit.prevent="submit">
                <FormField v-slot="{ id, describedBy, invalid }" :label="t('teams.fields.name')" :error="form.errors.name" required>
                    <BaseInput :id="id" v-model="form.name" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>
                <BaseButton type="submit" :loading="form.processing">{{ t('teams.create.submit') }}</BaseButton>
            </form>
        </BaseCard>
    </AppLayout>
</template>
