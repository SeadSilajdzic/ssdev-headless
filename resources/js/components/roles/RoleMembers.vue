<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import BasePagination from '@headless/components/ui/BasePagination.vue';
import EmptyState from '@headless/components/ui/EmptyState.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';

export interface RoleMember {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
}

export interface RoleMemberPage {
    data: RoleMember[];
    meta: { current_page: number; last_page: number; from: number | null; to: number | null; total: number };
}

const props = defineProps<{ members: RoleMemberPage; membersUrl: string; pageUrl: (page: number) => string; canManage: boolean }>();

const t = useTrans();
const form = useForm({ email: '' });
const removing = ref<number | null>(null);

function add(): void {
    form.post(props.membersUrl, { preserveScroll: true, onSuccess: () => form.reset() });
}

function remove(member: RoleMember): void {
    router.delete(`${props.membersUrl}/${member.id}`, {
        preserveScroll: true,
        onStart: () => (removing.value = member.id),
        onFinish: () => (removing.value = null),
    });
}
</script>

<template>
    <div class="space-y-5">
        <form v-if="canManage" class="flex flex-wrap items-end gap-3" novalidate data-test="add-member" @submit.prevent="add">
            <FormField v-slot="{ id, describedBy, invalid }" class="min-w-0 flex-1" :label="t('roles.members.email')" :error="form.errors.email">
                <BaseInput :id="id" v-model="form.email" type="email" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>
            <BaseButton type="submit" :loading="form.processing">{{ t('roles.members.add') }}</BaseButton>
        </form>

        <EmptyState v-if="!members.data.length" :title="t('roles.members.empty')" />

        <ul v-else class="divide-y divide-ink-100">
            <li v-for="member in members.data" :key="member.id" class="flex items-center gap-3 py-3">
                <BaseAvatar :name="member.name" :src="member.avatar_url" size="sm" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-ink-900">{{ member.name }}</p>
                    <p class="truncate text-xs text-ink-500">{{ member.email }}</p>
                </div>
                <BaseButton
                    v-if="canManage"
                    variant="ghost"
                    size="sm"
                    data-test="remove-member"
                    :loading="removing === member.id"
                    @click="remove(member)"
                >
                    {{ t('roles.members.remove') }}
                </BaseButton>
            </li>
        </ul>

        <BasePagination
            v-if="members.meta.last_page > 1"
            :current-page="members.meta.current_page"
            :last-page="members.meta.last_page"
            :from="members.meta.from"
            :to="members.meta.to"
            :total="members.meta.total"
            :url="pageUrl"
        />
    </div>
</template>
