import type { EventStatus } from '@/types';

export const eventStatusLabels: Record<EventStatus, string> = {
    upcoming: 'Upcoming',
    ongoing: 'Ongoing',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

export const eventStatusBadgeVariants: Record<
    EventStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    upcoming: 'outline',
    ongoing: 'default',
    completed: 'secondary',
    cancelled: 'destructive',
};

export const defaultCommissionRate = 7;

export const defaultMultiplier = 2;
