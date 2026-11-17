export type EventStatus = 'upcoming' | 'ongoing' | 'completed' | 'cancelled';

export type Event = {
    id: number;
    name: string;
    commission_rate: string;
    commission_amount: string;
    status: EventStatus;
    date: string;
    created_at: string;
    updated_at: string;
    fights_count?: number;
};

export type EventSummary = {
    total_commission: number;
    total_bets: number;
    total_draw_bets: number;
    total_cancelled_bets: number;
    total_payout_paid: number;
    total_cancelled_draw_paid: number;
    total_draw_paid: number;
    net_win_lose: number;
};

export type EventCashierTotals = {
    id: number;
    name: string;
    station: string | null;
    total_meron_bets: number;
    total_wala_bets: number;
    total_draw_bets: number;
    number_of_bets: number;
};

export type StationZReport = {
    station_id: number | null;
    station: string;
    transaction_count: number;
    gross_bets: number;
    draw_bets: number;
    cancelled_bets: number;
    sales: number;
};
