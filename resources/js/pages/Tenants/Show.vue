<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { computed, ref } from 'vue';
import TenantStatusBadge from '@headless/components/tenancy/TenantStatusBadge.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';
import type { TenantItem } from '@headless/pages/Tenants/Index.vue';

const props = defineProps<{
    tenant: TenantItem;
    can: { manage: boolean };
    indexUrl: string;
    updateUrl: string;
    suspensionUrl: string;
    ownerInvitationUrl: string;
    deleteUrl: string;
}>();

const t = useTrans();
const page = usePage();
const form = useForm({ name: props.tenant.name });
const deletion = useForm({ subdomain: '' });
const confirmingSuspend = ref(false);
const confirmingDelete = ref(false);
const busy = ref(false);

const error = computed(() => page.props.errors.owner ?? page.props.errors.tenant);
const suspended = computed(() => props.tenant.status === 'suspended');
const deletionConfirmed = computed(() => deletion.subdomain.trim().toLowerCase() === props.tenant.subdomain);

function save(): void {
    form.put(props.updateUrl, { preserveScroll: true });
}

function act(method: 'post' | 'delete', url: string, onDone?: () => void): void {
    router[method](url, {}, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => {
            busy.value = false;
            onDone?.();
        },
    });
}

function remove(): void {
    deletion.delete(props.deleteUrl, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="tenant.name" :breadcrumbs="[{ label: t('tenancy.title'), href: indexUrl }, { label: tenant.name }]">
        <div class="space-y-6">
            <BaseAlert v-if="error" variant="error" data-test="tenant-error">{{ error }}</BaseAlert>

            <BaseCard :title="t('tenancy.details.title')">
                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <TenantStatusBadge :status="tenant.status" />
                    <a :href="tenant.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-sm font-medium text-mint-700 hover:text-mint-800">
                        {{ tenant.url }}
                        <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
                        <span class="sr-only">{{ t('tenancy.details.open') }}</span>
                    </a>
                </div>

                <form v-if="can.manage" class="max-w-xl space-y-5" novalidate @submit.prevent="save">
                    <FormField v-slot="{ id, describedBy, invalid }" :label="t('tenancy.fields.name')" :error="form.errors.name" required>
                        <BaseInput :id="id" v-model="form.name" autocomplete="organization" :invalid="invalid" :aria-describedby="describedBy" />
                    </FormField>
                    <FormField v-slot="{ id, describedBy }" :label="t('tenancy.fields.subdomain')" :hint="t('tenancy.details.subdomain_locked')">
                        <BaseInput :id="id" :model-value="tenant.subdomain" readonly :aria-describedby="describedBy" />
                    </FormField>
                    <BaseButton type="submit" :loading="form.processing">{{ t('tenancy.details.save') }}</BaseButton>
                </form>

                <dl v-else class="space-y-1 text-sm" data-test="tenant-readonly">
                    <dt class="font-medium text-ink-700">{{ t('tenancy.fields.subdomain') }}</dt>
                    <dd class="text-ink-900">{{ tenant.subdomain }}</dd>
                </dl>
            </BaseCard>

            <BaseCard :title="t('tenancy.owner.title')" :description="t('tenancy.owner.description')">
                <div v-if="tenant.owner" class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-ink-900">{{ tenant.owner.email }}</span>
                    <span class="text-xs text-ink-500">{{ tenant.owner.accepted ? t('tenancy.owner.accepted') : t('tenancy.owner.pending') }}</span>
                    <BaseButton
                        v-if="can.manage && !tenant.owner.accepted"
                        variant="ghost"
                        size="sm"
                        data-test="resend-owner-invitation"
                        :disabled="busy"
                        @click="act('post', ownerInvitationUrl)"
                    >
                        {{ t('tenancy.owner.resend') }}
                    </BaseButton>
                </div>
                <p v-else class="text-sm text-ink-500">{{ t('tenancy.owner.none') }}</p>
            </BaseCard>

            <BaseCard v-if="can.manage" :title="t('tenancy.suspension.title')" :description="t('tenancy.suspension.description')">
                <BaseButton v-if="!suspended" variant="destructive" data-test="suspend-tenant" @click="confirmingSuspend = true">
                    {{ t('tenancy.suspension.suspend') }}
                </BaseButton>
                <BaseButton v-else variant="ghost" data-test="resume-tenant" :disabled="busy" @click="act('delete', suspensionUrl)">
                    {{ t('tenancy.suspension.resume') }}
                </BaseButton>
            </BaseCard>

            <BaseCard v-if="can.manage" :title="t('tenancy.delete.title')" :description="t('tenancy.delete.description')">
                <BaseButton variant="destructive" data-test="delete-tenant" @click="confirmingDelete = true">
                    {{ t('tenancy.delete.button') }}
                </BaseButton>
            </BaseCard>
        </div>

        <ConfirmDialog
            v-model:open="confirmingSuspend"
            :title="t('tenancy.suspension.confirm_title')"
            :description="t('tenancy.suspension.confirm_description')"
            :confirm-label="t('tenancy.suspension.suspend')"
            :loading="busy"
            destructive
            @confirm="act('post', suspensionUrl, () => (confirmingSuspend = false))"
        />
        <ConfirmDialog
            v-model:open="confirmingDelete"
            :title="t('tenancy.delete.confirm_title')"
            :description="t('tenancy.delete.confirm_description', { subdomain: tenant.subdomain })"
            :confirm-label="t('tenancy.delete.button')"
            :loading="deletion.processing"
            :confirm-disabled="!deletionConfirmed"
            destructive
            @confirm="remove"
        >
            <FormField v-slot="{ id, describedBy, invalid }" :label="t('tenancy.delete.confirm_field')" :error="deletion.errors.subdomain">
                <BaseInput
                    :id="id"
                    v-model="deletion.subdomain"
                    autocomplete="off"
                    :invalid="invalid"
                    :aria-describedby="describedBy"
                    data-test="delete-confirmation"
                />
            </FormField>
        </ConfirmDialog>
    </AppLayout>
</template>
