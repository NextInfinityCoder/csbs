<?php

namespace App\Actions;

use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Station;
use Illuminate\Support\Collection;

class GenerateEventZReport
{
    /**
     * Build the Z-report of every station that took bets in the event.
     *
     * Sales are the operator commission: the station's Meron and Wala bets on
     * fights that were not cancelled, multiplied by the event commission rate.
     * Bets taken by cashiers without a station are reported as "Unassigned".
     *
     * @return Collection<int, array{
     *     station_id: int|null,
     *     station: string,
     *     transaction_count: int,
     *     gross_bets: float,
     *     draw_bets: float,
     *     cancelled_bets: float,
     *     sales: float,
     * }>
     */
    public function handle(Event $event): Collection
    {
        $cancelled = FightResult::Cancelled->value;
        $commissionSides = [BetSide::Meron->value, BetSide::Wala->value];

        $stationTotals = Bet::query()
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->where('bets.event_id', $event->id)
            ->groupBy('bets.station_id')
            ->select('bets.station_id')
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result <> ? AND bets.bet_side IN (?, ?) THEN bets.amount END), 0) AS gross_bets',
                [$cancelled, ...$commissionSides],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result <> ? AND bets.bet_side = ? THEN bets.amount END), 0) AS draw_bets',
                [$cancelled, BetSide::Draw->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fights.result = ? THEN bets.amount END), 0) AS cancelled_bets',
                [$cancelled],
            )
            ->toBase()
            ->get();

        $stationIds = $stationTotals->pluck('station_id')->filter()->values();
        $stationNames = Station::query()->whereKey($stationIds)->pluck('name', 'id');

        return $stationTotals
            ->map(function (object $totals) use ($event, $stationNames): array {
                $sales = round((float) $totals->gross_bets * (float) $event->commission_rate / 100, 2);

                return [
                    'station_id' => $totals->station_id,
                    'station' => $totals->station_id === null
                        ? __('Unassigned')
                        : $stationNames[$totals->station_id],
                    'transaction_count' => (int) $totals->transaction_count,
                    'gross_bets' => round((float) $totals->gross_bets, 2),
                    'draw_bets' => round((float) $totals->draw_bets, 2),
                    'cancelled_bets' => round((float) $totals->cancelled_bets, 2),
                    'sales' => $sales,
                ];
            })
            ->sortBy(fn (array $report): string => $report['station_id'] === null ? "\u{FFFF}" : $report['station'], SORT_NATURAL)
            ->values();
    }
}
