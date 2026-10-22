<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseModal from '@headless/components/ui/BaseModal.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ deleteUrl: string }>();

const t = useTrans();
const open = ref(false);
const form = useForm({ password: '' });
// Set when a feature refuses the deletion, e.g. an owner who still has a team with other members.
const refusal = computed(() => usePage().props.errors.account);

function destroy(): void {
    form.delete(props.deleteUrl, {
        preserveScroll: true,
        onFinish: () => form.reset(),
    });
}

function cancel(): void {
    open.value = false;
    form.clearErrors();
    form.reset();
}
</script>

<template>
    <div>
        <BaseButton variant="destructive" @click="open = true">{{ t('settings.delete.button') }}</BaseButton>

        <BaseModal v-model:open="open" :title="t('settings.delete.confirm_title')" :description="t('settings.delete.confirm_description')">
            <form id="delete-account" class="space-y-4" novalidate @submit.prevent="destroy">
                <BaseAlert v-if="refusal" variant="error" data-test="delete-refused">{{ refusal }}</BaseAlert>
                <FormField v-slot="{ id, describedBy, invalid }" :label="t('auth.fields.password')" :error="form.errors.password" required>
                    <PasswordInput :id="id" v-model="form.password" :invalid="invalid" :aria-describedby="describedBy" />
                </FormField>
            </form>

            <template #footer>
                <BaseButton variant="ghost" @click="cancel">{{ t('common.actions.cancel') }}</BaseButton>
                <BaseButton variant="destructive" type="submit" form="delete-account" :loading="form.processing">{{ t('settings.delete.button') }}</BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
