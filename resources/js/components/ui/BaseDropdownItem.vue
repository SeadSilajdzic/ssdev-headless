<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { DropdownMenuItem } from 'reka-ui';

defineProps<{ href?: string; destructive?: boolean; method?: 'post' | 'delete' }>();

const emit = defineEmits<{ select: [] }>();
</script>

<template>
    <DropdownMenuItem
        :as-child="!!href"
        class="flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-sm outline-none"
        :class="destructive ? 'text-rose-600 data-[highlighted]:bg-rose-50' : 'text-ink-700 data-[highlighted]:bg-ink-50'"
        @select="emit('select')"
    >
        <Link v-if="href" :href="href" :method="method" :as="method ? 'button' : 'a'">
            <slot />
        </Link>
        <slot v-else />
    </DropdownMenuItem>
</template>
