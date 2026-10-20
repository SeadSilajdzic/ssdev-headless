import { readonly, ref, type Ref } from 'vue';

export const TOAST_DURATION_MS = 2500;

export type ToastType = 'success' | 'error';

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
}

const toasts = ref<Toast[]>([]);
let nextId = 1;

function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function push(type: ToastType, message: string): void {
    const id = nextId++;
    toasts.value = [...toasts.value, { id, type, message }];
    setTimeout(() => dismiss(id), TOAST_DURATION_MS);
}

export function useToast(): {
    toasts: Readonly<Ref<readonly Toast[]>>;
    success: (message: string) => void;
    error: (message: string) => void;
    dismiss: (id: number) => void;
} {
    return {
        toasts: readonly(toasts),
        success: (message) => push('success', message),
        error: (message) => push('error', message),
        dismiss,
    };
}
