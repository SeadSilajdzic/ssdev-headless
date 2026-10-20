<script setup lang="ts">
import { TooltipArrow, TooltipContent, TooltipPortal, TooltipProvider, TooltipRoot, TooltipTrigger } from 'reka-ui';

defineProps<{ content: string }>();
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <TooltipRoot>
            <TooltipTrigger as-child>
                <slot />
            </TooltipTrigger>
            <TooltipPortal>
                <TooltipContent :side-offset="6" class="z-50 rounded-md bg-ink-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white">
                    {{ content }}
                    <TooltipArrow class="fill-ink-900" />
                </TooltipContent>
            </TooltipPortal>
        </TooltipRoot>
    </TooltipProvider>
</template>
