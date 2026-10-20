<script setup lang="ts">
import { CircleAlert } from 'lucide-vue-next';
import { computed, useId } from 'vue';

const props = defineProps<{
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    id?: string;
}>();

const generatedId = useId();
const fieldId = computed(() => props.id ?? generatedId);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() => [props.hint && hintId.value, props.error && errorId.value].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div>
        <label :for="fieldId" class="mb-1.5 block text-sm font-medium text-ink-700">
            {{ label }}<span v-if="required" class="text-rose-600" aria-hidden="true"> *</span>
        </label>
        <slot :id="fieldId" :described-by="describedBy" :invalid="!!error" />
        <p v-if="hint" :id="hintId" class="mt-1.5 text-xs text-ink-500">{{ hint }}</p>
        <p v-if="error" :id="errorId" role="alert" class="mt-1.5 flex items-center gap-1 text-xs text-rose-600">
            <CircleAlert class="h-3.5 w-3.5" aria-hidden="true" />{{ error }}
        </p>
    </div>
</template>
