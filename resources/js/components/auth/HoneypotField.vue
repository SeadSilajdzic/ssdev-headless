<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

// Kept off-screen rather than display:none, so bots that skip hidden fields still fill it.
const model = defineModel<string>({ default: '' });
const page = usePage();
</script>

<template>
    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
        <input v-model="model" type="text" :name="page.props.headless.honeypot" tabindex="-1" autocomplete="off" />
    </div>
</template>
