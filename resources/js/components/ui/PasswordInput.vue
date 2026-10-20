<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';
import { inputClasses } from '@headless/components/ui/inputClasses';
import { useTrans } from '@headless/i18n';

withDefaults(defineProps<{ invalid?: boolean; autocomplete?: string; placeholder?: string }>(), { autocomplete: 'current-password' });

// Attributes from FormField (id, aria-*) belong on the input, not on the wrapper.
defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ default: '' });
const visible = ref(false);
const t = useTrans();
</script>

<template>
    <div class="relative">
        <input
            v-bind="$attrs"
            v-model="model"
            :type="visible ? 'text' : 'password'"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :aria-invalid="invalid ? 'true' : undefined"
            :class="[inputClasses(invalid), 'pr-11']"
        />
        <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-ink-400 hover:text-ink-700"
            :aria-pressed="visible ? 'true' : 'false'"
            :aria-label="visible ? t('common.password.hide') : t('common.password.show')"
            @click="visible = !visible"
        >
            <EyeOff v-if="visible" class="h-4 w-4" aria-hidden="true" />
            <Eye v-else class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>
</template>
