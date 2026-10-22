<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus, Settings } from '@lucide/vue';
import { computed } from 'vue';
import BaseDropdown from '@headless/components/ui/BaseDropdown.vue';
import BaseDropdownItem from '@headless/components/ui/BaseDropdownItem.vue';
import { useTrans } from '@headless/i18n';

const page = usePage();
const t = useTrans();
const teams = computed(() => page.props.teams ?? null);

function switchTo(id: number): void {
    if (!teams.value || teams.value.current?.id === id) {
        return;
    }

    router.put(teams.value.switchUrl, { team_id: id }, { preserveScroll: true });
}
</script>

<template>
    <BaseDropdown v-if="teams" :label="t('teams.switcher.label')">
        <template #trigger>
            <span class="flex w-56 items-center justify-between gap-2 rounded-lg border border-ink-100 px-3 py-2 text-left text-sm">
                <span class="truncate font-semibold text-ink-900">{{ teams.current?.name ?? t('teams.switcher.no_team') }}</span>
                <ChevronsUpDown class="h-4 w-4 shrink-0 text-ink-400" aria-hidden="true" />
            </span>
        </template>
        <BaseDropdownItem v-for="team in teams.all" :key="team.id" data-test="switch-team" @select="switchTo(team.id)">
            <Check v-if="team.id === teams.current?.id" class="h-4 w-4 text-mint-600" aria-hidden="true" />
            <span v-else class="h-4 w-4" aria-hidden="true" />
            <span class="truncate">{{ team.name }}</span>
        </BaseDropdownItem>
        <div v-if="teams.all.length" class="my-1 border-t border-ink-100" />
        <BaseDropdownItem v-if="teams.settingsUrl" :href="teams.settingsUrl">
            <Settings class="h-4 w-4 text-ink-500" aria-hidden="true" />
            {{ t('teams.switcher.settings') }}
        </BaseDropdownItem>
        <BaseDropdownItem :href="teams.createUrl">
            <Plus class="h-4 w-4 text-ink-500" aria-hidden="true" />
            {{ t('teams.switcher.create') }}
        </BaseDropdownItem>
    </BaseDropdown>
</template>
