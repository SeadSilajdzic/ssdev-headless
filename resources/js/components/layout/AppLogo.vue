<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LayoutGrid } from 'lucide-vue-next';

const page = usePage();
</script>

<template>
    <span class="flex items-center gap-2">
        <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-mint-400 to-mint-600">
            <LayoutGrid class="h-5 w-5 text-white" aria-hidden="true" />
        </span>
        <span class="font-display text-lg font-bold tracking-tight">{{ page.props.headless.appName }}</span>
    </span>
</template>
