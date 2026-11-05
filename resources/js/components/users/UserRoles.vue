<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCheckbox from '@headless/components/ui/BaseCheckbox.vue';
import { useTrans } from '@headless/i18n';

export interface UserRoleChoice {
    id: number;
    name: string;
    assigned: boolean;
    manageable: boolean;
}

const props = defineProps<{ roles: UserRoleChoice[]; rolesUrl: string; canManage: boolean }>();

const t = useTrans();
const saving = ref(false);
const selected = ref<number[]>(assignedManageable());

watch(
    () => props.roles,
    () => (selected.value = assignedManageable()),
);

const editable = computed(() => (props.canManage ? props.roles.filter((role) => role.manageable) : []));
// Roles the actor cannot change are sent back as they are, so saving never removes them.
const fixed = computed(() => props.roles.filter((role) => role.assigned && !(props.canManage && role.manageable)));

function assignedManageable(): number[] {
    return props.roles.filter((role) => role.assigned && role.manageable).map((role) => role.id);
}

function toggle(id: number, checked: boolean): void {
    selected.value = checked ? [...selected.value, id] : selected.value.filter((roleId) => roleId !== id);
}

function save(): void {
    router.put(
        props.rolesUrl,
        {
            roles: [...selected.value, ...fixed.value.map((role) => role.id)],
            // Lets the server refuse the save when someone else changed the roles after this page opened.
            known_roles: props.roles.filter((role) => role.assigned).map((role) => role.id),
        },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="editable.length" class="grid gap-2 sm:grid-cols-2">
            <BaseCheckbox
                v-for="role in editable"
                :key="role.id"
                :label="role.name"
                :model-value="selected.includes(role.id)"
                @update:model-value="(checked: boolean) => toggle(role.id, checked)"
            />
        </div>

        <div v-if="fixed.length" class="space-y-2">
            <p v-if="canManage" class="text-xs text-ink-500">{{ t('users.roles.locked') }}</p>
            <div class="flex flex-wrap gap-2">
                <BaseBadge v-for="role in fixed" :key="role.id" color="ink">{{ role.name }}</BaseBadge>
            </div>
        </div>

        <p v-if="!editable.length && !fixed.length" class="text-sm text-ink-500">{{ t('users.roles.none') }}</p>

        <BaseButton v-if="editable.length" data-test="save-roles" :loading="saving" @click="save">{{ t('users.roles.save') }}</BaseButton>
    </div>
</template>
