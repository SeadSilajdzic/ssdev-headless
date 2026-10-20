<script setup lang="ts">
import { computed } from 'vue';
import { useTrans } from '@headless/i18n';
import { scorePassword } from '@headless/support/passwordStrength';

const props = defineProps<{ password: string }>();

const t = useTrans();
const score = computed(() => scorePassword(props.password));
const labels = ['weak', 'weak', 'fair', 'good', 'strong'];
const colors = ['bg-ink-200', 'bg-rose-500', 'bg-orange-500', 'bg-amber-500', 'bg-mint-500'];
</script>

<template>
    <div class="mt-2 flex items-center gap-1.5">
        <div
            v-for="bar in 4"
            :key="bar"
            class="h-1 flex-1 rounded-full"
            :class="bar <= score ? colors[score] : 'bg-ink-200'"
            :data-active="bar <= score ? 'true' : 'false'"
        />
        <span class="ml-2 w-14 text-right text-xs text-ink-500" aria-live="polite">
            {{ password ? t(`common.password.strength.${labels[score]}`) : '' }}
        </span>
    </div>
</template>
