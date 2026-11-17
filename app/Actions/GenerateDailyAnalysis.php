<?php

namespace App\Actions;

use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class GenerateDailyAnalysis
{
    /**
     * Build the daily totals of bets, commission and payouts for the given number of days ending today.
     *
     * Total bets and commission follow the event summary: Meron and Wala bets on
     * fights that were not cancelled, grouped by the day the bet was placed.
     * Payouts are every claim paid (winnings, draw winnings and refunds),
     * grouped by the day the claim was paid.
     *
     * @return Collection<int, array{date: string, total_bets: float, total_commission: float, total_payout: float}>
     */
    public function handle(int $days, ?CarbonImmutable $today = null): Collection
    {
        $today ??= CarbonImmutable::today();
        $startDate = $today->subDays($days - 1)->startOfDay();
        $endDate = $today->endOfDay();

        $betTotals = Bet::query()
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->join('events', 'events.id', '=', 'bets.event_id')
            ->whereBetween('bets.created_at', [$startDate, $endDate])
            ->where('fights.result', '<>', FightResult::Cancelled->value)
            ->whereIn('bets.bet_side', [BetSide::Meron->value, BetSide::Wala->value])
            ->groupByRaw('DATE(bets.created_at)')
            ->selectRaw('DATE(bets.created_at) AS day')
            ->selectRaw('SUM(bets.amount) AS total_bets')
            ->selectRaw('SUM(bets.amount * events.commission_rate / 100) AS total_commission')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->day, 0, 10));

        $payoutTotals = Bet::query()
            ->whereNotNull('claimed_at')
            ->whereBetween('claimed_at', [$startDate, $endDate])
            ->groupByRaw('DATE(claimed_at)')
            ->selectRaw('DATE(claimed_at) AS day')
            ->selectRaw('SUM(claim_amount) AS total_payout')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->day, 0, 10));

        return collect(CarbonPeriod::create($startDate, '1 day', $today->startOfDay()))
            ->map(function (\DateTimeInterface $day) use ($betTotals, $payoutTotals): array {
                $date = $day->format('Y-m-d');
                $bets = $betTotals->get($date);

                return [
                    'date' => $date,
                    'total_bets' => round((float) ($bets->total_bets ?? 0), 2),
                    'total_commission' => round((float) ($bets->total_commission ?? 0), 2),
                    'total_payout' => round((float) ($payoutTotals->get($date)->total_payout ?? 0), 2),
                ];
            })
            ->values();
    }
}
