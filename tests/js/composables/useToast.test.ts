import { TOAST_DURATION_MS, useToast } from '@headless/composables/useToast';

it('queues toasts and removes them after the duration', () => {
    vi.useFakeTimers();
    const { toasts, success } = useToast();
    success('Saved.');
    expect(toasts.value.map((toast) => toast.message)).toEqual(['Saved.']);
    vi.advanceTimersByTime(TOAST_DURATION_MS);
    expect(toasts.value).toEqual([]);
    vi.useRealTimers();
});
