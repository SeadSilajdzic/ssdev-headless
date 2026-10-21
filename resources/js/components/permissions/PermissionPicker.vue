<script setup lang="ts">
import BaseCheckbox from '@headless/components/ui/BaseCheckbox.vue';

export interface PermissionGroup {
    group: string;
    label: string;
    abilities: { name: string }[];
}

defineProps<{ groups: PermissionGroup[]; emptyLabel: string }>();

const model = defineModel<string[]>({ required: true });

function toggle(name: string, checked: boolean): void {
    model.value = checked ? [...model.value, name] : model.value.filter((permission) => permission !== name);
}
</script>

<template>
    <div>
        <p v-if="!groups.length" class="text-sm text-ink-500">{{ emptyLabel }}</p>
        <div v-for="group in groups" :key="group.group" class="mb-3">
            <p class="mb-1 text-xs font-semibold tracking-wider text-ink-400 uppercase">{{ group.label }}</p>
            <div class="grid gap-2 sm:grid-cols-2">
                <BaseCheckbox
                    v-for="ability in group.abilities"
                    :key="ability.name"
                    :label="ability.name"
                    :model-value="model.includes(ability.name)"
                    @update:model-value="(checked: boolean) => toggle(ability.name, checked)"
                />
            </div>
        </div>
    </div>
</template>
