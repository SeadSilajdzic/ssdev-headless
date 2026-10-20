<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@headless/components/layout/AppLogo.vue';
import NavigationLinks from '@headless/components/layout/NavigationLinks.vue';
import type { NavigationSection } from '@headless/types';

defineProps<{ sections: NavigationSection[] }>();
</script>

<template>
    <aside class="sticky top-0 h-screen w-[260px] shrink-0 flex-col border-r border-ink-100 bg-white">
        <div class="px-6 py-5">
            <Link href="/" class="w-fit"><AppLogo /></Link>
        </div>
        <nav class="scrollbar-thin flex-1 overflow-y-auto px-3 pb-4">
            <NavigationLinks :sections="sections" />
        </nav>
    </aside>
</template>
