<script setup lang="ts">
import { computed } from 'vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';

const props = withDefaults(defineProps<{ people: { name: string; src?: string | null }[]; max?: number }>(), { max: 4 });

const visible = computed(() => props.people.slice(0, props.max));
const hidden = computed(() => props.people.length - visible.value.length);
</script>

<template>
    <div class="flex -space-x-2">
        <BaseAvatar v-for="person in visible" :key="person.name" :name="person.name" :src="person.src" size="sm" class="border-2 border-white" />
        <span v-if="hidden > 0" class="grid h-8 w-8 place-items-center rounded-full border-2 border-white bg-mint-100 text-xs font-semibold text-mint-800">
            +{{ hidden }}
        </span>
    </div>
</template>
