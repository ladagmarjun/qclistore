const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

export function money(value: string | number | null | undefined): string {
    return value === null || value === undefined || value === '' ? '—' : peso.format(Number(value));
}

export function date(value: string | null | undefined): string {
    return value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

/** "bank_transfer" → "Bank Transfer" */
export function label(value: string | null | undefined): string {
    return String(value ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

/** The first message from an Inertia errors bag. */
export function firstError(errors: Record<string, string | undefined>): string | undefined {
    return Object.values(errors).find(Boolean);
}
