import type { Cashier } from './cashier';
import type { Event } from './event';
import type { Fight } from './fight';
import type { Station } from './station';

export type BetSide = 'meron' | 'wala' | 'draw';

export type Bet = {
    id: number;
    barcode: string;
    event_id: number;
    fight_id: number;
    user_id: number;
    station_id: number | null;
    amount: string;
    bet_side: BetSide;
    claim_amount: string | null;
    claim_from_user_id: number | null;
    claimed_at: string | null;
    created_at: string;
    updated_at: string;
    event?: Pick<Event, 'id' | 'name'>;
    fight?: Pick<Fight, 'id' | 'fight_number'>;
    user?: Pick<Cashier, 'id' | 'name'>;
    station?: Pick<Station, 'id' | 'name'> | null;
    claimed_from?: Pick<Cashier, 'id' | 'name'> | null;
};
