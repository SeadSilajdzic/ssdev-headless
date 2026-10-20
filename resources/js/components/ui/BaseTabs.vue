<script setup lang="ts">
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';

withDefaults(defineProps<{ tabs: { value: string; label: string }[]; variant?: 'underline' | 'pill' }>(), { variant: 'underline' });

const model = defineModel<string>();
</script>

<template>
    <TabsRoot v-model="model">
        <TabsList :class="variant === 'underline' ? 'flex gap-6 border-b border-ink-200' : 'inline-flex rounded-lg bg-ink-100 p-1'">
            <TabsTrigger
                v-for="tab in tabs"
                :key="tab.value"
                :value="tab.value"
                :class="
                    variant === 'underline'
                        ? '-mb-px border-b-2 border-transparent pb-3 text-sm font-medium text-ink-500 hover:text-ink-900 data-[state=active]:border-mint-500 data-[state=active]:font-semibold data-[state=active]:text-ink-900'
                        : 'rounded-md px-3 py-1.5 text-sm font-medium text-ink-500 hover:text-ink-900 data-[state=active]:bg-white data-[state=active]:text-ink-900 data-[state=active]:shadow-sm'
                "
            >
                {{ tab.label }}
            </TabsTrigger>
        </TabsList>
        <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value" class="pt-6">
            <slot :name="tab.value" />
        </TabsContent>
    </TabsRoot>
</template>
