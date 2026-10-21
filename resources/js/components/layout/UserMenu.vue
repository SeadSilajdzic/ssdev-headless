<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';
import { computed } from 'vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
import BaseDropdown from '@headless/components/ui/BaseDropdown.vue';
import BaseDropdownItem from '@headless/components/ui/BaseDropdownItem.vue';
import { resolveNavigationIcon } from '@headless/icons';
import { useTrans } from '@headless/i18n';
import { accountLinks as findAccountLinks } from '@headless/support/navigation';

const page = usePage();
const t = useTrans();
const user = computed(() => page.props.auth.user);
const accountLinks = computed(() => findAccountLinks(page.props.headless.navigation));
</script>

<template>
    <BaseDropdown v-if="user" :label="t('common.layout.user_menu')" align="end">
        <template #trigger>
            <BaseAvatar :name="user.name" :src="user.avatar_url" />
            <span class="hidden text-left leading-tight sm:block">
                <span class="block text-sm font-semibold">{{ user.name }}</span>
                <span class="block text-xs text-ink-500">{{ user.email }}</span>
            </span>
        </template>
        <BaseDropdownItem v-for="link in accountLinks" :key="link.key" :href="link.url">
            <component :is="resolveNavigationIcon(link.icon)" class="h-4 w-4 text-ink-500" aria-hidden="true" />
            {{ link.label }}
        </BaseDropdownItem>
        <template v-if="page.props.headless.logoutUrl">
            <div v-if="accountLinks.length" class="my-1 border-t border-ink-100" />
            <BaseDropdownItem :href="page.props.headless.logoutUrl" method="post" destructive>
                <LogOut class="h-4 w-4" aria-hidden="true" />
                {{ t('common.actions.log_out') }}
            </BaseDropdownItem>
        </template>
    </BaseDropdown>
</template>
