export function inputClasses(invalid: boolean): string[] {
    return [
        'w-full rounded-btn border bg-ink-50 px-3.5 py-3 text-sm text-ink-900 transition placeholder:text-ink-400',
        'focus:bg-white focus:outline-none disabled:cursor-not-allowed disabled:opacity-60',
        invalid
            ? 'border-rose-400 shadow-[0_0_0_4px_rgba(244,63,94,0.15)] focus:border-rose-500'
            : 'border-ink-200 focus:border-mint-400 focus:shadow-ring-mint',
    ];
}
