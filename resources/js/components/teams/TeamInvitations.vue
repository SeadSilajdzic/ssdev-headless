<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import BaseSelect from '@headless/components/ui/BaseSelect.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';

export interface TeamInvitationItem {
    id: number;
    email: string;
    role_id: number | null;
    created_at: string | null;
}

const props = withDefaults(defineProps<{ invitations: TeamInvitationItem[]; invitationsUrl: string; roles?: { id: number; name: string }[] }>(), { roles: () => [] });

// Select options cannot be empty, so "no role" needs a value of its own.
const NO_ROLE = 'none';

const t = useTrans();
const form = useForm<{ email: string; role: string }>({ email: '', role: NO_ROLE });

function invite(): void {
    form.transform((data) => ({ email: data.email, role: data.role === NO_ROLE ? null : Number(data.role) })).post(props.invitationsUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function cancel(invitation: TeamInvitationItem): void {
    router.delete(`${props.invitationsUrl}/${invitation.id}`, { preserveScroll: true });
}

function roleName(id: number | null): string {
    return props.roles.find((role) => role.id === id)?.name ?? t('teams.invitations.no_role');
}
</script>

<template>
    <div class="space-y-6">
        <form class="flex flex-wrap items-end gap-3" novalidate data-test="invite-member" @submit.prevent="invite">
            <FormField v-slot="{ id, describedBy, invalid }" class="min-w-0 flex-1" :label="t('teams.fields.email')" :error="form.errors.email">
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>
            <FormField v-if="roles.length" class="w-48" :label="t('teams.fields.role')" :error="form.errors.role">
                <BaseSelect v-model="form.role" :options="[{ value: NO_ROLE, label: t('teams.invitations.no_role') }, ...roles.map((role) => ({ value: String(role.id), label: role.name }))]" />
            </FormField>
            <BaseButton type="submit" :loading="form.processing">{{ t('teams.invitations.invite') }}</BaseButton>
        </form>

        <div>
            <p class="mb-2 text-sm font-medium text-ink-700">{{ t('teams.invitations.pending') }}</p>
            <p v-if="!invitations.length" class="text-sm text-ink-500">{{ t('teams.invitations.none') }}</p>
            <ul v-else class="divide-y divide-ink-100">
                <li v-for="invitation in invitations" :key="invitation.id" class="flex flex-wrap items-center gap-3 py-3">
                    <span class="min-w-0 flex-1 truncate text-sm text-ink-900">{{ invitation.email }}</span>
                    <span v-if="roles.length" class="text-xs text-ink-500">{{ roleName(invitation.role_id) }}</span>
                    <BaseButton variant="ghost" size="sm" data-test="cancel-invitation" @click="cancel(invitation)">{{ t('teams.invitations.cancel') }}</BaseButton>
                </li>
            </ul>
        </div>
    </div>
</template>
