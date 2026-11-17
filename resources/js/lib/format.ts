const amountFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function formatAmount(amount: string | number): string {
    return amountFormatter.format(Number(amount));
}

export function formatDateTime(dateTime: string | null): string {
    return dateTime ? dateTimeFormatter.format(new Date(dateTime)) : '—';
}
