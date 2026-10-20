<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseModal from '@headless/components/ui/BaseModal.vue';
import FormField from '@headless/components/ui/FormField.vue';
import PasswordInput from '@headless/components/ui/PasswordInput.vue';
import { useTrans } from '@headless/i18n';

const props = defineProps<{ deleteUrl: string }>();

const t = useTrans();
const open = ref(false);
const form = useForm({ password: '' });

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
            <form id="delete-account" novalidate @submit.prevent="destroy">
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
