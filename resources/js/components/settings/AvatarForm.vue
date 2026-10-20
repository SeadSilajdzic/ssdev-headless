<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ uploadUrl: string; accept: string[]; maxKilobytes: number }>();

const t = useTrans();
const page = usePage();
const user = computed(() => page.props.auth.user);
const input = ref<HTMLInputElement | null>(null);
const form = useForm<{ avatar: File | null }>({ avatar: null });
const removing = ref(false);
const hint = computed(() =>
    t('settings.avatar.hint', {
        formats: props.accept.map((extension) => extension.slice(1).toUpperCase()).join(', '),
        size: props.maxKilobytes,
    }),
);

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    form.avatar = file;
    form.post(props.uploadUrl, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.reset();

            if (input.value) {
                input.value.value = '';
            }
        },
    });
}

function remove(): void {
    router.delete(props.uploadUrl, {
        preserveScroll: true,
        onStart: () => (removing.value = true),
        onFinish: () => (removing.value = false),
    });
}
</script>

<template>
    <div v-if="user" class="flex flex-wrap items-center gap-5">
        <BaseAvatar :name="user.name" :src="user.avatar_url" size="lg" />
        <div class="space-y-2">
            <div class="flex flex-wrap gap-2">
                <BaseButton variant="ghost" size="sm" :loading="form.processing" @click="input?.click()">{{ t('settings.avatar.change') }}</BaseButton>
                <BaseButton v-if="user.avatar_url" variant="link" size="sm" :loading="removing" @click="remove">{{ t('settings.avatar.remove') }}</BaseButton>
            </div>
            <p class="text-xs text-ink-500">{{ hint }}</p>
            <p v-if="form.errors.avatar" role="alert" class="text-xs text-rose-600">{{ form.errors.avatar }}</p>
        </div>
        <input ref="input" type="file" class="hidden" :accept="accept.join(',')" @change="upload" />
    </div>
</template>
