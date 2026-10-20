<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseSpinner from '@headless/components/ui/BaseSpinner.vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'ghost' | 'accent' | 'destructive' | 'link';
        size?: 'sm' | 'md' | 'lg';
        type?: 'button' | 'submit' | 'reset';
        href?: string;
        loading?: boolean;
        disabled?: boolean;
    }>(),
    { variant: 'primary', size: 'md', type: 'button' },
);

const variants = {
    primary: 'bg-mint-900 text-white hover:bg-[#0F3D3D]',
    ghost: 'border-ink-200 bg-transparent text-ink-600 hover:bg-ink-50 hover:text-ink-900',
    accent: 'bg-mint-500 text-white hover:bg-mint-600',
    destructive: 'bg-rose-600 text-white hover:bg-rose-700',
    link: 'text-mint-700 hover:bg-mint-50',
};

const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-[18px] py-2.5 text-sm',
    lg: 'px-5 py-3 text-base',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-btn border border-transparent font-semibold transition active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50',
    variants[props.variant],
    sizes[props.size],
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading" :aria-busy="loading ? 'true' : undefined">
        <BaseSpinner v-if="loading" />
        <slot />
    </button>
</template>
