<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCheckbox from '@headless/components/ui/BaseCheckbox.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ memberId: number; roles: { id: number; name: string }[]; assigned: number[]; url?: string }>();

const t = useTrans();
const selected = ref<number[]>([...props.assigned]);
const saving = ref(false);
const assignedRoles = computed(() => props.roles.filter((role) => props.assigned.includes(role.id)));

function toggle(id: number, checked: boolean): void {
    selected.value = checked ? [...selected.value, id] : selected.value.filter((roleId) => roleId !== id);
}

function save(): void {
    if (!props.url) {
        return;
    }

    router.put(props.url, { roles: selected.value }, { preserveScroll: true, onStart: () => (saving.value = true), onFinish: () => (saving.value = false) });
}
</script>

<template>
    <div class="w-full space-y-2 pl-11">
        <template v-if="url">
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                <BaseCheckbox
                    v-for="role in roles"
                    :key="role.id"
                    :label="role.name"
                    :model-value="selected.includes(role.id)"
                    @update:model-value="(checked: boolean) => toggle(role.id, checked)"
                />
            </div>
            <BaseButton variant="ghost" size="sm" :data-test="`save-member-roles-${memberId}`" :loading="saving" @click="save">{{ t('teams.members.save_roles') }}</BaseButton>
        </template>
        <div v-else class="flex flex-wrap gap-2">
            <BaseBadge v-for="role in assignedRoles" :key="role.id" color="ink">{{ role.name }}</BaseBadge>
            <span v-if="!assignedRoles.length" class="text-xs text-ink-500">{{ t('teams.members.no_roles') }}</span>
        </div>
    </div>
</template>
