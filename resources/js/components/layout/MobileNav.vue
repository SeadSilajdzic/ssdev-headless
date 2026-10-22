<script setup lang="ts">
import BaseDrawer from '@headless/components/ui/BaseDrawer.vue';
import NavigationLinks from '@headless/components/layout/NavigationLinks.vue';
import TeamSwitcher from '@headless/components/teams/TeamSwitcher.vue';
import type { NavigationSection } from '@headless/types';

defineProps<{ sections: NavigationSection[]; title: string }>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <BaseDrawer v-model:open="open" :title="title" side="left">
        <div class="mb-4 empty:hidden">
            <TeamSwitcher />
        </div>
        <nav>
            <NavigationLinks :sections="sections" />
        </nav>
    </BaseDrawer>
</template>
