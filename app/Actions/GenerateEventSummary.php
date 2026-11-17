<?php

namespace App\Actions;

use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

class GenerateEventSummary
{
    /**
     * Build the over-all summary and the per-cashier totals for an event.
     *
     * @return array{
     *     summary: array{
     *         total_commission: float,
     *         total_bets: float,
     *         total_draw_bets: float,
     *         total_cancelled_bets: float,
     *         total_payout_paid: float,
     *         total_cancelled_draw_paid: float,
     *         total_draw_paid: float,
     *         net_win_lose: float,
     *     },
     *     cashiers: Collection<int, array{
     *         id: int,
     *         name: string,
     *         station: string|null,
     *         total_meron_bets: float,
     *         total_wala_bets: float,
     *         total_draw_bets: float,
     *         number_of_bets: int,
     *     }>,
     * }
     */
    public function handle(Event $event): array
    {
        return [
            'summary' => $this->summary($event),
            'cashiers' => $this->cashierTotals($event),
        ];
    }

    /**
     * Calculate the over-all summary figures for the event.
     *
     * @return array{
     *     total_commission: float,
     *     total_bets: float,
     *     total_draw_bets: float,
     *     total_cancelled_bets: float,
     *     total_payout_paid: float,
     *     total_cancelled_draw_paid: float,
     *     total_draw_paid: float,
     *     net_win_lose: float,
     * }
     */
    private function summary(Event $event): array
    {
        $meron = BetSide::Meron->value;
        $wala = BetSide::Wala->value;
        $draw = BetSide::Draw->value;
        $cancelled = FightResult::Cancelled->value;
        $drawResult = FightResult::Draw->value;

        $totals = Bet::query()
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->where('bets.event_id', $event->id)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result <> ? AND bets.bet_side IN (?, ?) THEN bets.amount END), 0) AS total_bets',
                [$cancelled, $meron, $wala],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result <> ? AND bets.bet_side = ? THEN bets.amount END), 0) AS total_draw_bets',
                [$cancelled, $draw],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result = ? THEN bets.amount END), 0) AS total_cancelled_bets',
                [$cancelled],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN bets.claimed_at IS NOT NULL AND bets.bet_side IN (?, ?) AND bets.bet_side = fights.result THEN bets.claim_amount END), 0) AS total_payout_paid',
                [$meron, $wala],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN bets.claimed_at IS NOT NULL AND (fights.result = ? OR (fights.result = ? AND bets.bet_side IN (?, ?))) THEN bets.claim_amount END), 0) AS total_cancelled_draw_paid',
                [$cancelled, $drawResult, $meron, $wala],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN bets.claimed_at IS NOT NULL AND bets.bet_side = ? AND fights.result = ? THEN bets.claim_amount END), 0) AS total_draw_paid',
                [$draw, $drawResult],
            )
            ->toBase()
            ->first();

        $totalBets = (float) $totals->total_bets;
        $totalDrawBets = (float) $totals->total_draw_bets;
        $totalCancelledBets = (float) $totals->total_cancelled_bets;
        $totalPayoutPaid = (float) $totals->total_payout_paid;
        $totalCancelledDrawPaid = (float) $totals->total_cancelled_draw_paid;
        $totalDrawPaid = (float) $totals->total_draw_paid;

        $totalCollected = $totalBets + $totalDrawBets + $totalCancelledBets;
        $totalPaid = $totalPayoutPaid + $totalCancelledDrawPaid + $totalDrawPaid;

        return [
            'total_commission' => round($totalBets * (float) $event->commission_rate / 100, 2),
            'total_bets' => round($totalBets, 2),
            'total_draw_bets' => round($totalDrawBets, 2),
            'total_cancelled_bets' => round($totalCancelledBets, 2),
            'total_payout_paid' => round($totalPayoutPaid, 2),
            'total_cancelled_draw_paid' => round($totalCancelledDrawPaid, 2),
            'total_draw_paid' => round($totalDrawPaid, 2),
            'net_win_lose' => round($totalCollected - $totalPaid, 2),
        ];
    }

    /**
     * Calculate the bet totals of every cashier who took a bet in the event.
     *
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     station: string|null,
     *     total_meron_bets: float,
     *     total_wala_bets: float,
     *     total_draw_bets: float,
     *     number_of_bets: int,
     * }>
     */
    private function cashierTotals(Event $event): Collection
    {
        $betTotals = Bet::query()
            ->where('event_id', $event->id)
            ->groupBy('user_id')
            ->select('user_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN bet_side = ? THEN amount END), 0) AS total_meron_bets', [BetSide::Meron->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN bet_side = ? THEN amount END), 0) AS total_wala_bets', [BetSide::Wala->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN bet_side = ? THEN amount END), 0) AS total_draw_bets', [BetSide::Draw->value])
            ->selectRaw('COUNT(*) AS number_of_bets')
            ->toBase()
            ->get()
            ->keyBy('user_id');

        return User::query()
            ->with('station:id,name')
            ->whereKey($betTotals->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'station_id'])
            ->map(function (User $cashier) use ($betTotals): array {
                $bets = $betTotals->get($cashier->id);

                return [
                    'id' => $cashier->id,
                    'name' => $cashier->name,
                    'station' => $cashier->station?->name,
                    'total_meron_bets' => round((float) $bets->total_meron_bets, 2),
                    'total_wala_bets' => round((float) $bets->total_wala_bets, 2),
                    'total_draw_bets' => round((float) $bets->total_draw_bets, 2),
                    'number_of_bets' => (int) $bets->number_of_bets,
                ];
            })
            ->values();
    }
}
