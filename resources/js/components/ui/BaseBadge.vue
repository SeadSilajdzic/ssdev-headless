<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { useTrans } from '@headless/i18n';

const props = withDefaults(
    defineProps<{ color?: 'mint' | 'amber' | 'rose' | 'blue' | 'ink'; dot?: boolean; removable?: boolean }>(),
    { color: 'mint' },
);

const emit = defineEmits<{ remove: [] }>();
const t = useTrans();

const colors = {
    mint: { badge: 'bg-mint-100 text-mint-800', dot: 'bg-mint-600' },
    amber: { badge: 'bg-amber-100 text-amber-800', dot: 'bg-amber-500' },
    rose: { badge: 'bg-rose-100 text-rose-700', dot: 'bg-rose-600' },
    blue: { badge: 'bg-blue-100 text-blue-700', dot: 'bg-blue-600' },
    ink: { badge: 'bg-ink-100 text-ink-700', dot: 'bg-ink-500' },
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 text-xs font-semibold"
        :class="[colors[props.color].badge, removable ? 'rounded-pill px-3 py-1' : 'rounded-md px-2 py-0.5']"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="colors[props.color].dot" aria-hidden="true" />
        <slot />
        <button v-if="removable" type="button" :aria-label="t('common.actions.delete')" @click="emit('remove')">
            <X class="h-3 w-3" aria-hidden="true" />
        </button>
    </span>
</template>
