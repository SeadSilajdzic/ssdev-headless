export type PasswordScore = 0 | 1 | 2 | 3 | 4;

export function scorePassword(password: string): PasswordScore {
    const checks = [
        password.length >= 8,
        /[a-z]/.test(password) && /[A-Z]/.test(password),
        /[0-9]/.test(password),
        /[^A-Za-z0-9]/.test(password),
    ];

    return checks.filter(Boolean).length as PasswordScore;
}
