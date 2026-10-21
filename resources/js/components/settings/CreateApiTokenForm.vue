<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCheckbox from '@headless/components/ui/BaseCheckbox.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';

export interface PermissionGroup {
    group: string;
    label: string;
    abilities: { name: string }[];
}

const props = defineProps<{ permissions: PermissionGroup[]; storeUrl: string; createdToken: string | null }>();

const t = useTrans();
const form = useForm<{ name: string; abilities: string[] }>({ name: '', abilities: [] });
const copied = ref(false);

function toggle(ability: string, checked: boolean): void {
    form.abilities = checked ? [...form.abilities, ability] : form.abilities.filter((name) => name !== ability);
}

function submit(): void {
    form.post(props.storeUrl, { preserveScroll: true, onSuccess: () => form.reset() });
}

async function copy(): Promise<void> {
    await navigator.clipboard.writeText(props.createdToken ?? '');
    copied.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <BaseAlert v-if="createdToken" variant="success" :title="t('settings.api_tokens.created')">
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <code class="rounded-lg bg-white px-3 py-2 font-mono text-sm break-all text-ink-900">{{ createdToken }}</code>
                <BaseButton variant="ghost" size="sm" data-test="copy-token" @click="copy">
                    {{ copied ? t('settings.api_tokens.copied') : t('settings.api_tokens.copy') }}
                </BaseButton>
            </div>
        </BaseAlert>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField v-slot="{ id, describedBy, invalid }" :label="t('settings.api_tokens.name')" :error="form.errors.name" required>
                <BaseInput :id="id" v-model="form.name" :invalid="invalid" :aria-describedby="describedBy" />
            </FormField>

            <fieldset>
                <legend class="mb-2 text-sm font-medium text-ink-700">{{ t('settings.api_tokens.permissions') }}</legend>
                <p v-if="!permissions.length" class="text-sm text-ink-500">{{ t('settings.api_tokens.no_permissions') }}</p>
                <div v-for="group in permissions" :key="group.group" class="mb-3">
                    <p class="mb-1 text-xs font-semibold tracking-wider text-ink-400 uppercase">{{ group.label }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <BaseCheckbox
                            v-for="ability in group.abilities"
                            :key="ability.name"
                            :label="ability.name"
                            :model-value="form.abilities.includes(ability.name)"
                            @update:model-value="(checked: boolean) => toggle(ability.name, checked)"
                        />
                    </div>
                </div>
                <p v-if="form.errors.abilities" role="alert" class="mt-1.5 text-xs text-rose-600">{{ form.errors.abilities }}</p>
            </fieldset>

            <div class="flex justify-end">
                <BaseButton type="submit" :loading="form.processing">{{ t('settings.api_tokens.create') }}</BaseButton>
            </div>
        </form>
    </div>
</template>
