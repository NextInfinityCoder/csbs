import type { Event } from './event';

export type FightResult = 'pending' | 'meron' | 'wala' | 'draw' | 'cancelled';

export type Fight = {
    id: number;
    event_id: number;
    fight_number: number;
    meron_amount: string;
    wala_amount: string;
    draw_amount: string;
    meron_odds: string;
    wala_odds: string;
    percentage: string;
    commission: string;
    result: FightResult;
    bet_open: string | null;
    bet_closed: string | null;
    canceled_at: string | null;
    created_at: string;
    updated_at: string;
    event?: Pick<Event, 'id' | 'name'>;
    bets_count?: number;
};
