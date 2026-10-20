import { usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';

export function useHoneypot(trap: Ref<string>): <T extends object>(data: T) => T & Record<string, string> {
    const page = usePage();

    return (data) => ({ ...data, [page.props.headless.honeypot]: trap.value });
}
