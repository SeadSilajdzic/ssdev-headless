<script setup lang="ts">
import { Menu } from 'lucide-vue-next';
import BaseIconButton from '@headless/components/ui/BaseIconButton.vue';
import UserMenu from '@headless/components/layout/UserMenu.vue';
import { useTrans } from '@headless/i18n';

const emit = defineEmits<{ openMenu: [] }>();
const t = useTrans();
</script>

<template>
    <header class="sticky top-0 z-10 border-b border-ink-100 bg-mint-50/80 backdrop-blur">
        <div class="flex items-center gap-4 px-4 py-4 sm:px-8">
            <BaseIconButton class="lg:hidden" :label="t('common.layout.open_menu')" @click="emit('openMenu')">
                <Menu class="h-5 w-5" aria-hidden="true" />
            </BaseIconButton>
            <div class="flex-1" />
            <slot name="actions" />
            <div class="border-l border-ink-200 pl-3">
                <UserMenu />
            </div>
        </div>
    </header>
</template>
