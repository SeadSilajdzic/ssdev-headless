<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import TeamMemberRoles from '@headless/components/teams/TeamMemberRoles.vue';
import { useTrans } from '@headless/i18n';

export interface TeamMember {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    is_owner: boolean;
}

const props = withDefaults(
    defineProps<{
        members: TeamMember[];
        isOwner: boolean;
        ownerUrl: string;
        removeMemberUrls: Record<number, string>;
        // Only with the roles feature.
        roles?: { id: number; name: string }[];
        memberRoles?: Record<number, number[]>;
        memberRolesUrls?: Record<number, string>;
    }>(),
    { roles: () => [], memberRoles: () => ({}), memberRolesUrls: () => ({}) },
);

const t = useTrans();
const handingOver = ref<TeamMember | null>(null);
const busy = ref(false);

function remove(member: TeamMember): void {
    const url = props.removeMemberUrls[member.id];

    if (url) {
        router.delete(url, { preserveScroll: true });
    }
}

function handOver(): void {
    if (!handingOver.value) {
        return;
    }

    router.put(props.ownerUrl, { user_id: handingOver.value.id }, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => {
            busy.value = false;
            handingOver.value = null;
        },
    });
}
</script>

<template>
    <ul class="divide-y divide-ink-100">
        <li v-for="member in members" :key="member.id" class="flex flex-wrap items-center gap-3 py-3">
            <BaseAvatar :name="member.name" :src="member.avatar_url" size="sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ink-900">{{ member.name }}</p>
                <p class="truncate text-xs text-ink-500">{{ member.email }}</p>
            </div>
            <BaseBadge v-if="member.is_owner" color="mint">{{ t('teams.members.owner') }}</BaseBadge>
            <template v-else-if="isOwner">
                <BaseButton variant="ghost" size="sm" data-test="make-owner" @click="handingOver = member">{{ t('teams.members.make_owner') }}</BaseButton>
                <BaseButton variant="ghost" size="sm" data-test="remove-member" @click="remove(member)">{{ t('teams.members.remove') }}</BaseButton>
            </template>
            <TeamMemberRoles
                v-if="roles.length"
                :member-id="member.id"
                :roles="roles"
                :assigned="memberRoles[member.id] ?? []"
                :url="memberRolesUrls[member.id]"
            />
        </li>
    </ul>

    <ConfirmDialog
        :open="handingOver !== null"
        :title="t('teams.members.transfer_title')"
        :description="t('teams.members.transfer_description')"
        :confirm-label="t('teams.members.make_owner')"
        :loading="busy"
        @update:open="(open: boolean) => !open && (handingOver = null)"
        @confirm="handOver"
    />
</template>
