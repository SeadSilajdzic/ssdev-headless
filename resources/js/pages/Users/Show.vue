<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UserStatusBadge from '@headless/components/users/UserStatusBadge.vue';
import BaseAlert from '@headless/components/ui/BaseAlert.vue';
import BaseAvatar from '@headless/components/ui/BaseAvatar.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import BaseCard from '@headless/components/ui/BaseCard.vue';
import BaseInput from '@headless/components/ui/BaseInput.vue';
import ConfirmDialog from '@headless/components/ui/ConfirmDialog.vue';
import FormField from '@headless/components/ui/FormField.vue';
import { useTrans } from '@headless/i18n';
import AppLayout from '@headless/layouts/AppLayout.vue';
import type { ManagedUser } from '@headless/pages/Users/Index.vue';

const props = defineProps<{
    user: ManagedUser;
    isSelf: boolean;
    can: { update: boolean; block: boolean; delete: boolean; invite: boolean };
    indexUrl: string;
    updateUrl: string;
    blockUrl: string;
    invitationUrl: string;
    passwordResetUrl: string;
    sessionsUrl: string;
    deleteUrl: string;
}>();

const t = useTrans();
const page = usePage();
const form = useForm({ name: props.user.name, email: props.user.email });
const confirmingBlock = ref(false);
const confirmingDelete = ref(false);
const busy = ref(false);

const error = computed(() => page.props.errors.user ?? page.props.errors.role);
const invited = computed(() => props.user.status === 'invited');
const blocked = computed(() => props.user.status === 'blocked');

function save(): void {
    form.put(props.updateUrl, { preserveScroll: true });
}

function act(method: 'post' | 'delete', url: string, onDone?: () => void): void {
    router[method](url, {}, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => {
            busy.value = false;
            onDone?.();
        },
    });
}
</script>

<template>
    <AppLayout :title="user.name" :breadcrumbs="[{ label: t('users.title'), href: indexUrl }, { label: user.name }]">
        <div class="space-y-6">
            <BaseAlert v-if="error" variant="error" data-test="user-error">{{ error }}</BaseAlert>

            <BaseCard :title="t('users.details.title')">
                <div class="mb-5 flex items-center gap-3">
                    <BaseAvatar :name="user.name" :src="user.avatar_url" />
                    <div class="flex flex-wrap items-center gap-2">
                        <UserStatusBadge :status="user.status" />
                        <span v-if="!user.email_verified" class="text-xs text-ink-500">{{ t('users.unverified') }}</span>
                    </div>
                </div>

                <form v-if="can.update" class="max-w-xl space-y-5" novalidate @submit.prevent="save">
                    <FormField v-slot="{ id, describedBy, invalid }" :label="t('users.fields.name')" :error="form.errors.name" required>
                        <BaseInput :id="id" v-model="form.name" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                    </FormField>
                    <FormField v-slot="{ id, describedBy, invalid }" :label="t('users.fields.email')" :error="form.errors.email" required>
                        <BaseInput :id="id" v-model="form.email" type="email" autocomplete="off" :invalid="invalid" :aria-describedby="describedBy" />
                    </FormField>
                    <BaseButton type="submit" :loading="form.processing">{{ t('users.details.save') }}</BaseButton>
                </form>

                <dl v-else class="space-y-1 text-sm" data-test="user-readonly">
                    <dt class="font-medium text-ink-700">{{ t('users.fields.email') }}</dt>
                    <dd class="text-ink-900">{{ user.email }}</dd>
                </dl>
            </BaseCard>

            <BaseCard :title="t('users.access.title')" :description="t('users.access.description')">
                <div class="flex flex-wrap gap-3">
                    <BaseButton v-if="invited && can.invite" variant="ghost" data-test="resend-invitation" :disabled="busy" @click="act('post', invitationUrl)">
                        {{ t('users.access.resend_invitation') }}
                    </BaseButton>
                    <BaseButton v-if="!invited && can.update" variant="ghost" data-test="send-password-reset" :disabled="busy" @click="act('post', passwordResetUrl)">
                        {{ t('users.access.send_password_reset') }}
                    </BaseButton>
                    <BaseButton v-if="!isSelf && can.update" variant="ghost" data-test="end-sessions" :disabled="busy" @click="act('delete', sessionsUrl)">
                        {{ t('users.access.end_sessions') }}
                    </BaseButton>
                    <BaseButton v-if="!isSelf && can.block && !blocked" variant="destructive" data-test="block-user" @click="confirmingBlock = true">
                        {{ t('users.access.block') }}
                    </BaseButton>
                    <BaseButton v-if="can.block && blocked" variant="ghost" data-test="unblock-user" :disabled="busy" @click="act('delete', blockUrl)">
                        {{ t('users.access.unblock') }}
                    </BaseButton>
                    <BaseButton v-if="!isSelf && can.delete" variant="destructive" data-test="delete-user" @click="confirmingDelete = true">
                        {{ t('users.delete.button') }}
                    </BaseButton>
                </div>
            </BaseCard>
        </div>

        <ConfirmDialog
            v-model:open="confirmingBlock"
            :title="t('users.access.block_confirm_title')"
            :description="t('users.access.block_confirm_description')"
            :confirm-label="t('users.access.block')"
            :loading="busy"
            destructive
            @confirm="act('post', blockUrl, () => (confirmingBlock = false))"
        />
        <ConfirmDialog
            v-model:open="confirmingDelete"
            :title="t('users.delete.confirm_title')"
            :description="t('users.delete.confirm_description')"
            :confirm-label="t('users.delete.button')"
            :loading="busy"
            destructive
            @confirm="act('delete', deleteUrl, () => (confirmingDelete = false))"
        />
    </AppLayout>
</template>
