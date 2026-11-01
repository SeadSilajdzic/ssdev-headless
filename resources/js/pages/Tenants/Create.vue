<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const props = defineProps<{ baseDomain: string; indexUrl: string; storeUrl: string }>();

const t = useTrans();
const form = useForm({ name: '', subdomain: '', owner_email: '' });

function submit(): void {
    form.post(props.storeUrl);
}
</script>

<template>
    <AppLayout :title="t('tenancy.create.title')" :breadcrumbs="[{ label: t('tenancy.title'), href: indexUrl }, { label: t('tenancy.create.title') }]">
        <BaseCard :description="t('tenancy.create.description')">
            <form class="max-w-xl space-y-5" novalidate @submit.prevent="submit">
                <FormField v-slot="{ id, describedBy, invalid }" :label="t('tenancy.fields.name')" :error="form.errors.name" required>
                    <BaseInput :id="id" v-model="form.name" autocomplete="organization" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>

                <FormField v-slot="{ id, describedBy, invalid }" :label="t('tenancy.fields.subdomain')" :hint="t('tenancy.fields.subdomain_hint')" :error="form.errors.subdomain" required>
                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1">
                            <BaseInput :id="id" v-model="form.subdomain" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                        </div>
                        <span class="shrink-0 text-sm text-ink-500" data-test="base-domain">.{{ baseDomain }}</span>
                    </div>
                </FormField>

                <FormField v-slot="{ id, describedBy, invalid }" :label="t('tenancy.fields.owner_email')" :error="form.errors.owner_email" required>
                    <BaseInput :id="id" v-model="form.owner_email" type="email" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>

                <BaseButton type="submit" :loading="form.processing">{{ t('tenancy.create.submit') }}</BaseButton>
            </form>
        </BaseCard>
    </AppLayout>
</template>
