<?php

namespace App\Actions;

use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EncodeFight
{
    public const int MINIMUM_BET = 100;

    public const int INSERT_CHUNK_SIZE = 500;

    /**
     * Create a fight with generated bets and claims from the encoded totals.
     *
     * Winning bets are claimed at the odds; on a draw or cancelled fight every
     * bet is refunded at its own amount and no commission is earned.
     *
     * @param  array{
     *     fight_number: int,
     *     meron_amount: int,
     *     meron_bettors: int,
     *     wala_amount: int,
     *     wala_bettors: int,
     *     date: string,
     *     time_open: string,
     *     time_close: string,
     *     result: string,
     * }  $entry
     * @param  Collection<int, User>  $cashiers
     */
    public function handle(Event $event, array $entry, Collection $cashiers): Fight
    {
        $result = FightResult::from($entry['result']);
        $betOpen = CarbonImmutable::parse("{$entry['date']} {$entry['time_open']}");
        $betClosed = CarbonImmutable::parse("{$entry['date']} {$entry['time_close']}");
        $commissionRate = (float) $event->commission_rate;
        $meronAmount = (int) $entry['meron_amount'];
        $walaAmount = (int) $entry['wala_amount'];

        $odds = [
            BetSide::Meron->value => Fight::calculateOdds($meronAmount, $walaAmount, $commissionRate),
            BetSide::Wala->value => Fight::calculateOdds($walaAmount, $meronAmount, $commissionRate),
        ];

        return DB::transaction(function () use ($event, $entry, $cashiers, $result, $betOpen, $betClosed, $commissionRate, $meronAmount, $walaAmount, $odds): Fight {
            $fight = $event->fights()->create([
                'fight_number' => $entry['fight_number'],
                'meron_amount' => $meronAmount,
                'wala_amount' => $walaAmount,
                'draw_amount' => 0,
                'meron_odds' => $odds[BetSide::Meron->value],
                'wala_odds' => $odds[BetSide::Wala->value],
                'percentage' => $commissionRate,
                'commission' => $result->isRefunded()
                    ? 0
                    : round(($meronAmount + $walaAmount) * $commissionRate / 100, 2),
                'result' => $result,
                'bet_open' => $betOpen,
                'bet_closed' => $betClosed,
                'canceled_at' => $result === FightResult::Cancelled ? $betClosed : null,
            ]);

            $plannedBets = collect([
                ...$this->planSide(BetSide::Meron, $meronAmount, (int) $entry['meron_bettors'], $betOpen, $betClosed),
                ...$this->planSide(BetSide::Wala, $walaAmount, (int) $entry['wala_bettors'], $betOpen, $betClosed),
            ])->sortBy('placed_at')->values();

            $this->insertBets($event, $fight, $plannedBets->all(), $cashiers, $result, $odds, $betClosed);

            $event->increment('commission_amount', (float) $fight->commission);

            return $fight;
        });
    }

    /**
     * Save the planned bets with as few queries as possible.
     *
     * Barcodes are reserved with one lookup, station numbers with one locked
     * lookup, and the rows are inserted in chunks.
     *
     * @param  list<array{side: BetSide, amount: int, placed_at: CarbonImmutable}>  $plannedBets
     * @param  Collection<int, User>  $cashiers
     * @param  array<string, float>  $odds
     */
    private function insertBets(
        Event $event,
        Fight $fight,
        array $plannedBets,
        Collection $cashiers,
        FightResult $result,
        array $odds,
        CarbonImmutable $betClosed,
    ): void {
        if ($plannedBets === []) {
            return;
        }

        $isRefunded = $result->isRefunded();
        $assignedCashiers = array_map(fn (): User => $cashiers->random(), $plannedBets);
        $barcodes = Bet::generateUniqueBarcodes(count($plannedBets));
        $stationSequences = Bet::lockLatestStationSequences(
            collect($assignedCashiers)->pluck('station_id')->filter()->unique()->values()->all(),
        );

        $rows = [];

        foreach ($plannedBets as $index => $plannedBet) {
            $cashier = $assignedCashiers[$index];
            $side = $plannedBet['side'];
            $claim = null;

            if ($side->value === $result->value) {
                $claim = [
                    'amount' => round($plannedBet['amount'] * $odds[$side->value] / 100, 2),
                    'claimed_at' => $this->pickClaimTime($betClosed),
                ];
            } elseif ($isRefunded) {
                $claim = [
                    'amount' => $plannedBet['amount'],
                    'claimed_at' => $this->pickRefundTime($betClosed),
                ];
            }

            $rows[] = [
                'barcode' => $barcodes[$index],
                'event_id' => $event->id,
                'fight_id' => $fight->id,
                'user_id' => $cashier->id,
                'station_id' => $cashier->station_id,
                'station_sequence' => $cashier->station_id === null
                    ? null
                    : ++$stationSequences[$cashier->station_id],
                'amount' => $plannedBet['amount'],
                'bet_side' => $side->value,
                'claim_amount' => $claim['amount'] ?? null,
                'claim_from_user_id' => $claim === null ? null : $this->pickClaimingCashier($cashier, $cashiers)->id,
                'claimed_at' => $claim['claimed_at'] ?? null,
                'created_at' => $plannedBet['placed_at'],
                'updated_at' => $claim['claimed_at'] ?? $plannedBet['placed_at'],
            ];
        }

        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            Bet::query()->insert(array_map(fn (array $row): array => [
                ...$row,
                'claimed_at' => $row['claimed_at']?->toDateTimeString(),
                'created_at' => $row['created_at']->toDateTimeString(),
                'updated_at' => $row['updated_at']->toDateTimeString(),
            ], $chunk));
        }
    }

    /**
     * Split a side's total into one whole-peso bet per bettor, each at least the minimum bet.
     *
     * The split is uneven like real betting: a few large bets and many small ones.
     * Amounts are rounded to 50 or 10 pesos whenever the total allows it.
     *
     * @return list<int>
     */
    public function distributeAmount(int $total, int $bettors): array
    {
        if ($bettors === 0) {
            return [];
        }

        $remainder = $total - $bettors * self::MINIMUM_BET;
        $step = match (true) {
            $remainder % 50 === 0 => 50,
            $remainder % 10 === 0 => 10,
            default => 1,
        };

        $weights = array_map(fn (): float => (-log(mt_rand(1, mt_getrandmax()) / mt_getrandmax())) ** 1.5, range(1, $bettors));
        $weightTotal = array_sum($weights) ?: 1;

        $extras = array_map(
            fn (float $weight): int => intdiv((int) floor($remainder * $weight / $weightTotal), $step) * $step,
            $weights,
        );

        for ($leftover = $remainder - array_sum($extras); $leftover > 0; $leftover -= $step) {
            $extras[array_rand($extras)] += $step;
        }

        $amounts = array_map(fn (int $extra): int => self::MINIMUM_BET + $extra, $extras);
        shuffle($amounts);

        return $amounts;
    }

    /**
     * Plan the bets of one side with their amounts and placement times.
     *
     * @return list<array{side: BetSide, amount: int, placed_at: CarbonImmutable}>
     */
    private function planSide(BetSide $side, int $total, int $bettors, CarbonImmutable $betOpen, CarbonImmutable $betClosed): array
    {
        $bettingSeconds = max(0, $betOpen->diffInSeconds($betClosed));

        return array_map(fn (int $amount): array => [
            'side' => $side,
            'amount' => $amount,
            'placed_at' => $betOpen->addSeconds(random_int(0, (int) $bettingSeconds)),
        ], $this->distributeAmount($total, $bettors));
    }

    /**
     * Pick who pays the claim: usually the cashier who took the bet, sometimes another cashier.
     *
     * @param  Collection<int, User>  $cashiers
     */
    private function pickClaimingCashier(User $betCashier, Collection $cashiers): User
    {
        $otherCashiers = $cashiers->reject(fn (User $cashier): bool => $cashier->is($betCashier));

        if ($otherCashiers->isEmpty() || random_int(1, 100) > 10) {
            return $betCashier;
        }

        return $otherCashiers->random();
    }

    /**
     * Pick when a draw or cancelled bet was refunded: 30 seconds to 5 minutes after closing.
     */
    private function pickRefundTime(CarbonImmutable $betClosed): CarbonImmutable
    {
        return $betClosed->addSeconds(random_int(30, 5 * 60));
    }

    /**
     * Pick when the claim was paid: 3-7 minutes after closing, or 7-20 minutes for a few late claims.
     */
    private function pickClaimTime(CarbonImmutable $betClosed): CarbonImmutable
    {
        $secondsAfterClosing = random_int(1, 100) <= 5
            ? random_int(7 * 60 + 1, 20 * 60)
            : random_int(3 * 60, 7 * 60);

        return $betClosed->addSeconds($secondsAfterClosing);
    }
}
