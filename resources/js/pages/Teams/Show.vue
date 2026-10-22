<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TeamInvitations, { type TeamInvitationItem } from '@headless/components/teams/TeamInvitations.vue';
import TeamMembers, { type TeamMember } from '@headless/components/teams/TeamMembers.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';

const props = defineProps<{
    team: { id: number; name: string; personal: boolean; is_owner: boolean; is_current: boolean };
    isOwner: boolean;
    members: TeamMember[];
    invitations: TeamInvitationItem[];
    updateUrl: string;
    deleteUrl: string;
    leaveUrl: string;
    ownerUrl: string;
    invitationsUrl: string;
    removeMemberUrls: Record<number, string>;
}>();

const t = useTrans();
const page = usePage();
const form = useForm({ name: props.team.name });
const confirmingDelete = ref(false);
const confirmingLeave = ref(false);
const busy = ref(false);

const error = computed(() => page.props.errors.team);

function rename(): void {
    form.put(props.updateUrl, { preserveScroll: true });
}

function remove(url: string, close: () => void): void {
    router.delete(url, {
        onStart: () => (busy.value = true),
        onFinish: () => {
            busy.value = false;
            close();
        },
    });
}
</script>

<template>
    <AppLayout :title="team.name">
        <div class="space-y-6">
            <BaseAlert v-if="error" variant="error" data-test="team-error">{{ error }}</BaseAlert>

            <BaseCard :title="t('teams.details.title')">
                <BaseBadge v-if="team.personal" class="mb-4" color="ink">{{ t('teams.details.personal') }}</BaseBadge>
                <form v-if="isOwner" class="flex max-w-xl flex-wrap items-end gap-3" novalidate data-test="rename-team" @submit.prevent="rename">
                    <FormField v-slot="{ id, describedBy, invalid }" class="min-w-0 flex-1" :label="t('teams.fields.name')" :error="form.errors.name" required>
                        <BaseInput :id="id" v-model="form.name" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                    </FormField>
                    <BaseButton type="submit" :loading="form.processing">{{ t('teams.details.save') }}</BaseButton>
                </form>
                <p v-else class="text-sm text-ink-900">{{ team.name }}</p>
            </BaseCard>

            <BaseCard :title="t('teams.members.title')" :description="t('teams.members.description')">
                <TeamMembers :members="members" :is-owner="isOwner" :owner-url="ownerUrl" :remove-member-urls="removeMemberUrls" />
            </BaseCard>

            <BaseCard v-if="isOwner" :title="t('teams.invitations.title')" :description="t('teams.invitations.description')">
                <TeamInvitations :invitations="invitations" :invitations-url="invitationsUrl" />
            </BaseCard>

            <div class="flex justify-end">
                <BaseButton v-if="isOwner" variant="destructive" data-test="delete-team" @click="confirmingDelete = true">{{ t('teams.delete.button') }}</BaseButton>
                <BaseButton v-else variant="destructive" data-test="leave-team" @click="confirmingLeave = true">{{ t('teams.leave.button') }}</BaseButton>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="confirmingDelete"
            :title="t('teams.delete.confirm_title')"
            :description="t('teams.delete.confirm_description')"
            :confirm-label="t('teams.delete.button')"
            :loading="busy"
            destructive
            @confirm="remove(deleteUrl, () => (confirmingDelete = false))"
        />
        <ConfirmDialog
            v-model:open="confirmingLeave"
            :title="t('teams.leave.confirm_title')"
            :description="t('teams.leave.confirm_description')"
            :confirm-label="t('teams.leave.button')"
            :loading="busy"
            destructive
            @confirm="remove(leaveUrl, () => (confirmingLeave = false))"
        />
    </AppLayout>
</template>
