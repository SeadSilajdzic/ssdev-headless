<script setup lang="ts">
import { CircleCheck, CircleX, Info, TriangleAlert, X } from '@lucide/vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{
    variant: 'success' | 'warning' | 'error' | 'info';
    title?: string;
    dismissible?: boolean;
}>();

const emit = defineEmits<{ dismiss: [] }>();
const t = useTrans();

const styles = {
    success: { box: 'border-mint-200 bg-mint-50', icon: CircleCheck, iconColor: 'text-mint-700', title: 'text-mint-900', text: 'text-mint-800' },
    warning: { box: 'border-amber-200 bg-amber-50', icon: TriangleAlert, iconColor: 'text-amber-700', title: 'text-amber-900', text: 'text-amber-800' },
    error: { box: 'border-rose-200 bg-rose-50', icon: CircleX, iconColor: 'text-rose-700', title: 'text-rose-900', text: 'text-rose-800' },
    info: { box: 'border-blue-200 bg-blue-50', icon: Info, iconColor: 'text-blue-700', title: 'text-blue-900', text: 'text-blue-800' },
};

const style = styles[props.variant];
</script>

<template>
    <div :role="variant === 'error' ? 'alert' : 'status'" class="flex items-start gap-3 rounded-xl border p-4" :class="style.box">
        <component :is="style.icon" class="mt-0.5 h-5 w-5 shrink-0" :class="style.iconColor" aria-hidden="true" />
        <div class="flex-1">
            <div v-if="title" class="text-sm font-semibold" :class="style.title">{{ title }}</div>
            <div class="mt-0.5 text-xs" :class="style.text"><slot /></div>
        </div>
        <button v-if="dismissible" type="button" :class="style.iconColor" :aria-label="t('common.actions.close')" @click="emit('dismiss')">
            <X class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>
</template>
