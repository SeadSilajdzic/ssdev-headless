import { usePage } from '@inertiajs/vue3';

export function useCan(): (permission: string) => boolean {
    const page = usePage();

    return (permission) => page.props.auth.permissions.includes(permission);
}
