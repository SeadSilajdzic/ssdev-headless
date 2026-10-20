<script setup lang="ts">
import { DropdownMenuContent, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuTrigger } from 'reka-ui';

withDefaults(defineProps<{ label?: string; align?: 'start' | 'end' }>(), { align: 'start' });
</script>

<template>
    <DropdownMenuRoot :modal="false">
        <DropdownMenuTrigger :aria-label="label" class="inline-flex items-center gap-2 rounded-btn">
            <slot name="trigger" />
        </DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent :align="align" :side-offset="6" class="z-50 w-56 rounded-xl border border-ink-100 bg-white py-2 shadow-pop">
                <slot />
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
