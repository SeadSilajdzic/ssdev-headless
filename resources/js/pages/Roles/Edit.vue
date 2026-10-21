<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PermissionPicker, { type PermissionGroup } from '@headless/components/permissions/PermissionPicker.vue';
import RoleMembers, { type RoleMemberPage } from '@headless/components/roles/RoleMembers.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

export interface RoleDetail {
    id: number;
    name: string;
    permissions: string[];
    users_count: number;
    permissions_count: number;
}

const props = defineProps<{
    role: RoleDetail | null;
    permissions: PermissionGroup[];
    can: { manage: boolean };
    readOnlyReason: string | null;
    indexUrl: string;
    saveUrl: string;
    deleteUrl: string | null;
    members: RoleMemberPage | null;
    membersUrl: string | null;
}>();

const t = useTrans();
const page = usePage();
const confirmingDelete = ref(false);
const deleting = ref(false);
const form = useForm({ name: props.role?.name ?? '', permissions: [...(props.role?.permissions ?? [])] });

const title = computed(() => props.role?.name ?? t('roles.new'));
const roleError = computed(() => page.props.errors.role);

function submit(): void {
    const options = { preserveScroll: true };

    if (props.role) {
        form.put(props.saveUrl, options);
    } else {
        form.post(props.saveUrl, options);
    }
}

function membersPageUrl(page: number): string {
    return `${props.indexUrl}/${props.role?.id}?members_page=${page}`;
}

function destroy(): void {
    if (!props.deleteUrl) {
        return;
    }

    router.delete(props.deleteUrl, {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirmingDelete.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :title="title" :breadcrumbs="[{ label: t('roles.title'), href: indexUrl }, { label: title }]">
        <div class="space-y-6">
            <BaseAlert v-if="roleError" variant="error" data-test="role-error">{{ roleError }}</BaseAlert>
            <BaseAlert v-else-if="readOnlyReason" variant="info" data-test="role-read-only">{{ readOnlyReason }}</BaseAlert>

            <BaseCard :title="role ? t('roles.edit') : t('roles.new')">
                <form v-if="can.manage" class="space-y-5" novalidate @submit.prevent="submit">
                    <FormField v-slot="{ id, describedBy, invalid }" :label="t('roles.fields.name')" :error="form.errors.name" required>
                        <BaseInput :id="id" v-model="form.name" :invalid="invalid" :aria-describedby="describedBy" />
                    </FormField>

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-ink-700">{{ t('roles.fields.permissions') }}</legend>
                        <PermissionPicker v-model="form.permissions" :groups="permissions" :empty-label="t('roles.no_permissions')" />
                        <p v-if="form.errors.permissions" role="alert" class="mt-1.5 text-xs text-rose-600">{{ form.errors.permissions }}</p>
                    </fieldset>

                    <div class="flex flex-wrap justify-between gap-3">
                        <BaseButton v-if="deleteUrl" variant="destructive" data-test="delete-role" @click="confirmingDelete = true">
                            {{ t('roles.delete.button') }}
                        </BaseButton>
                        <BaseButton type="submit" class="ml-auto" :loading="form.processing">{{ t('roles.save') }}</BaseButton>
                    </div>
                </form>

                <div v-else-if="role" class="space-y-3" data-test="role-readonly">
                    <p class="text-sm font-medium text-ink-700">{{ t('roles.fields.permissions') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <BaseBadge v-for="permission in role.permissions" :key="permission" color="ink">{{ permission }}</BaseBadge>
                    </div>
                </div>
            </BaseCard>

            <BaseCard v-if="role && members && membersUrl" :title="t('roles.members.title')" :description="t('roles.members.description')">
                <RoleMembers :members="members" :members-url="membersUrl" :page-url="membersPageUrl" :can-manage="can.manage" />
            </BaseCard>
        </div>

        <ConfirmDialog
            v-model:open="confirmingDelete"
            :title="t('roles.delete.confirm_title')"
            :description="t('roles.delete.confirm_description')"
            :confirm-label="t('roles.delete.button')"
            :loading="deleting"
            destructive
            @confirm="destroy"
        />
    </AppLayout>
</template>
