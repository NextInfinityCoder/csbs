import type { VariantProps } from 'class-variance-authority';
import type { badgeVariants } from '@/components/ui/badge';
import type { BetSide } from '@/types';

export const betSideLabels: Record<BetSide, string> = {
    meron: 'Meron',
    wala: 'Wala',
    draw: 'Draw',
};

export const betSideBadgeVariants: Record<
    BetSide,
    NonNullable<VariantProps<typeof badgeVariants>['variant']>
> = {
    meron: 'destructive',
    wala: 'info',
    draw: 'success',
};
