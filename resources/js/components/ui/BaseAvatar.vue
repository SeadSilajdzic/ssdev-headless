<script setup lang="ts">
import { AvatarFallback, AvatarImage, AvatarRoot } from 'reka-ui';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ name: string; src?: string | null; size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' });

const sizes = { sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-20 w-20 text-xl' };

const initials = computed(() => {
    const words = props.name.trim().split(/\s+/).filter(Boolean);
    const letters = words.length > 1 ? [words[0], words[words.length - 1]] : words;

    return letters.map((word) => word[0]?.toUpperCase() ?? '').join('');
});
</script>

<template>
    <AvatarRoot class="inline-grid shrink-0 place-items-center overflow-hidden rounded-full bg-mint-100 font-semibold text-mint-800" :class="sizes[size]">
        <AvatarImage v-if="src" :src="src" :alt="name" class="h-full w-full object-cover" />
        <AvatarFallback>{{ initials }}</AvatarFallback>
    </AvatarRoot>
</template>
