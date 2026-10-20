<script setup lang="ts">
import BaseDrawer from '@headless/components/ui/BaseDrawer.vue';
import NavigationLinks from '@headless/components/layout/NavigationLinks.vue';
import type { NavigationSection } from '@headless/types';

defineProps<{ sections: NavigationSection[]; title: string }>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <BaseDrawer v-model:open="open" :title="title" side="left">
        <nav>
            <NavigationLinks :sections="sections" />
        </nav>
    </BaseDrawer>
</template>
