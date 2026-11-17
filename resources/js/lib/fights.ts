import type { VariantProps } from 'class-variance-authority';
import type { badgeVariants } from '@/components/ui/badge';
import type { FightResult } from '@/types';

export const fightResultLabels: Record<FightResult, string> = {
    pending: 'Pending',
    meron: 'Meron',
    wala: 'Wala',
    draw: 'Draw',
    cancelled: 'Cancelled',
};

export const fightResultBadgeVariants: Record<
    FightResult,
    NonNullable<VariantProps<typeof badgeVariants>['variant']>
> = {
    pending: 'warning',
    meron: 'destructive',
    wala: 'info',
    draw: 'success',
    cancelled: 'muted',
};

/**
 * Payout percentage of a side, rounded down to one decimal.
 * Mirrors Fight::calculateOdds() on the server.
 */
export function calculateOdds(
    amount: number,
    againstAmount: number,
    commissionRate: number,
): number {
    if (amount <= 0) {
        return 0;
    }

    const percentage = (100 - commissionRate) / 100;
    const odds = (100 * (amount + againstAmount) * percentage) / amount;

    return Math.floor(Math.round(odds * 10 * 1e6) / 1e6) / 10;
}
